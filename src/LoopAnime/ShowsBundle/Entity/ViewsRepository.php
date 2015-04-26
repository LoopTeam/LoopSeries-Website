<?php

namespace LoopAnime\ShowsBundle\Entity;

use Doctrine\ORM\EntityRepository;
use LoopAnime\UsersBundle\Entity\Users;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * ViewsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ViewsRepository extends EntityRepository
{

    public function getTotViews(Users $user, $completed = true, $idAnime = null)
    {
        $completed = $completed ? 1 : 0;

        $idUser = $user->getId();
        $query = $this->createQueryBuilder("views")
            ->select('COUNT(views.id)')
            ->where("views.idUser = :idUser")
            ->andWhere("views.completed = :completed")
            ->setParameter('idUser',$idUser)
            ->setParameter('completed',$completed);
        if(!empty($idAnime)) {
            $query->andWhere("views.idAnime = :idAnime")
                ->setParameter("idAnime",$idAnime);
        }
        $query = $query->getQuery();
        return $query->getSingleScalarResult();
    }

    /**
     * @param Users|null $user
     * @param $idEpisode
     * @return bool
     */
    public function isEpisodeSeen($user, $idEpisode)
    {
        if($user === null)
            return false;
        $r = $this->createQueryBuilder('views')
                ->select('views.id')
                ->where('views.idUser = :idUser')
                ->andWhere('views.idEpisode = :idEpisode')
                ->andWhere('views.completed = 1')
                ->setParameter('idUser',$user->getId())
                ->setParameter('idEpisode',$idEpisode)
                ->getQuery()
                ->getOneOrNullResult();
        if($r !== null) {
            return true;
        }
        return false;
    }

    private function getView(Users $user, $episode)
    {
        if (!$episode instanceof AnimesEpisodes) {
            $idEpisode = $episode;
            $episode = $this->getEntityManager()->getRepository('LoopAnimeShowsBundle:AnimesEpisodes')->find($episode);
            if (!$episode) {
                throw new NotFoundHttpException('Episode with the id '.$idEpisode.' was not found!');
            }
        }

        $view = $this->findOneBy(['idUser' => $user->getId(), 'idEpisode' => $episode->getId()]);
        if (!$view) {
            $view = new Views();
            $view->setIdUser($user->getId());
            $view->setIdEpisode($episode->getId());
            $view->setAnimeEpisodes($episode);
            $view->setIdAnime($episode->getSeason()->getAnime()->getId());
            $view->setWatchedTime(0);
            $view->setViewTime(new \DateTime("now"));
        }
        return $view;
    }

    /**
     * @param Users $user
     * @param $idEpisode
     * @param integer|null $idLink
     * @return bool
     */
    public function setEpisodeAsSeen(Users $user, $idEpisode, $idLink = null)
    {
        if (empty($idLink)) {
            $idLink = 0;
        }

        $view = $this->getView($user, $idEpisode);
        $view->setIdLink((int)$idLink);
        $view->setCompleted(1);

        $this->_em->persist($view);
        $this->_em->flush();
        return true;
    }

    /**
     * @param Users $user
     * @param $idEpisode
     * @param integer|null $idLink
     * @return bool
     */
    public function setEpisodeAsUnseen(Users $user, $idEpisode, $idLink = null)
    {
        if (empty($idLink)) {
            $idLink = 0;
        }
        $view = $this->getView($user, $idEpisode);
        $view->setIdLink((int)$idLink);
        $view->setCompleted(0);

        $this->_em->persist($view);
        $this->_em->flush();

        return true;
    }

    public function setViewProgress(Users $user, $idEpisode, $idLink, $watchedTime)
    {
        if (!empty($idEpisode) && !empty($idLink)) {
            $view = $this->getView($user, $idEpisode);

            $view->setWatchedTime((int) $watchedTime);
            $view->setViewTime(new \DateTime("now"));

            $this->_em->persist($view);
            $this->_em->flush();
        } else {
            throw new \Exception("Id Episode and Id Link cannot be empty");
        }
        return true;
    }

    /**
     * @param Users $user
     * @param $idEpisode
     * @throws \Exception
     * @return array|boolean
     */
    public function getViewProgress(Users $user, $idEpisode)
    {
        if(!empty($idEpisode)) {
            /** @var Views $view */
            $view = $this->findOneBy(['idUser' => $user->getId(), 'idEpisode' => $idEpisode]);

            if($view === null) {
                return false;
            } else {
                return [
                    'idLink' => $view->getIdLink(),
                    'watchedTime' => $view->getWatchedTime(),
                    'viewTime' => $view->getViewTime()->format("d-m-Y H:m:s")
                ];
            }
        } else {
            throw new \Exception("Id Episode cannot be empty");
        }
    }


    public function setEpisodesAsSeenBulk($myWatchedEpisodes, Users $user, Animes $animeObj)
    {
        // Get Seasons
        /** @var AnimesSeasonsRepository $animesSeasonsRepo */
        $animesSeasonsRepo = $this->getEntityManager()->getRepository('LoopAnime\ShowsBundle\Entity\AnimesSeasons');
        /** @var AnimesEpisodesRepository $animesEpisodesRepo */
        $animesEpisodesRepo = $this->getEntityManager()->getRepository('LoopAnime\ShowsBundle\Entity\AnimesEpisodes');
        /** @var AnimesSeasons[] $seasons */
        $seasons = $animesSeasonsRepo->getSeasonsByAnime($animeObj->getId(),true);
        foreach($seasons as $season) {
            $episodes = $animesEpisodesRepo->getEpisodesBySeason($season->getId(),true);
            foreach($episodes as $episode) {
                /** @var AnimesEpisodes $episode */
                $episode = $episode[0];

                // If the absolute number is higher than the all watched episodes -- Stops all cycles
                if($episode->getAbsoluteNumber() > $myWatchedEpisodes) {
                    break(2);
                }
                if(!$this->isEpisodeSeen($user, $episode->getId()))
                    $this->setEpisodeAsSeen($user, $episode->getId(), 0);
            }
        }
        return true;
    }

    public function getIncompleteViews(Users $user)
    {
        $query = $this->createQueryBuilder('views')
                    ->select('views')
                    ->addSelect('ae')
                    ->join('views.animeEpisodes','ae')
                    ->where('views.idUser = :idUser')
                    ->andWhere('views.completed = 0')
                    ->setParameter('idUser', $user->getId())
                    ->getQuery();
        return $query->getResult();
    }

}
