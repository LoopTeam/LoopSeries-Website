<?php

namespace LoopAnime\ShowsBundle\Entity;

use Doctrine\ORM\EntityRepository;
use FOS\UserBundle\FOSUserBundle;
use FOS\UserBundle\Model\User;
use LoopAnime\UsersBundle\Entity\Users;

/**
 * animes_episodesRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AnimesEpisodesRepository extends EntityRepository
{

    public function getMostViewsEpisodes($getResults = true)
    {
        $q = $this->createQueryBuilder('ae')
                ->where('ae.airDate <= CURRENT_TIMESTAMP()')
                ->orderBy('ae.views','DESC')
                ->addOrderBy('ae.views','DESC')
                ->getQuery();

        if($getResults) {
            return $q->getResult();
        } else {
            return $q;
        }
    }

    public function getMostRatedEpisodes($getResults = true)
    {
        $q = $this->createQueryBuilder('ae')
                ->select('ae')
                ->where('ae.airDate <= CURRENT_TIMESTAMP()')
                ->orderBy('ae.rating','DESC')
                ->addOrderBy('ae.ratingCount','DESC')
                ->addOrderBy('ae.ratingUp','DESC')
                ->getQuery();

        if($getResults) {
            return $q->getResult();
        } else {
            return $q;
        }
    }

    public function getUserHistoryEpisodes(Users $user, $getResults = true)
    {

        $userId = $user->getId();
        $q = $this->createQueryBuilder('ae')
            ->select('ae')
            ->join('ae.views','views')
            ->where('views.idUser = :idUser')
            ->andWhere('ae.airDate <= CURRENT_TIMESTAMP()')
            ->orderBy('views.viewTime','DESC')
            ->setParameter('idUser',$userId)
            ->getQuery();

        if($getResults)
            return $q->getResult();
        else
            return $q;
    }

    /**
     * @param $idAnime
     * @param bool $getResults
     * @return array|\Doctrine\ORM\Query
     */
    public function getEpisodesByAnime($idAnime, $getResults = true) {
        $query = $this->createQueryBuilder('ae')
            ->select('ae')
            ->addSelect('a.id')
            ->addSelect('a.title')
            ->addSelect('ase.season')
            ->join('ae.animesSeasons','ase')
            ->join('ase.animes','a')
            ->where('a.id = :idAnime')
            ->setParameter('idAnime',$idAnime)
            ->getQuery();

        if($getResults)
            return $query->getResult();
        else
            return $query;
    }

    /**
     * @param $idSeason
     * @param bool $getResults
     * @return array|\Doctrine\ORM\Query
     */
    public function getEpisodesBySeason($idSeason, $getResults = true) {
        $query = "SELECT ae, ase.season
                FROM
                    LoopAnime\ShowsBundle\Entity\AnimesEpisodes ae
                    JOIN ae.animesSeasons ase
                WHERE
                    ase.id = '".$idSeason."'";
        if($getResults)
            return $this->_em->createQuery($query)->getResult();
        else
            return $this->_em->createQuery($query);
    }

    /**
     * @param $idEpisode
     * @param bool $nextEpisode
     * @throws \Exception
     * @internal param bool $getResults
     * @return array|\Doctrine\ORM\Query
     */
    public function getNavigateEpisode($idEpisode, $nextEpisode = true) {

        /** @var AnimesEpisodes $episode */
        $episode = $this->find($idEpisode);

        if($episode === null) {
            throw new \Exception("This episode does not exist -- I shouldnt be here!");
        }

        /** @var AnimesSeasonsRepository $seasonsRepo */
        $seasonsRepo = $this->_em->getRepository('LoopAnime\ShowsBundle\Entity\AnimesSeasons');
        /** @var AnimesSeasons $season */
        $season = $seasonsRepo->find($episode->getIdSeason());

        if($nextEpisode) {
            $lookUpEpisode = $episode->getEpisode() + 1;
            $lookUpSeason = $season->getSeason() + 1;
        } else {
            $lookUpEpisode = $episode->getEpisode() - 1;
            $lookUpSeason = $season->getSeason() - 1;
        }

        // Try find the next episode by episode number
        $query = "SELECT ae, ase.season
                FROM
                    LoopAnime\ShowsBundle\Entity\AnimesEpisodes ae
                    JOIN ae.animesSeasons ase
                WHERE
                    ae.episode = '".$lookUpEpisode."'
                    AND ae.idSeason = '".$episode->getIdSeason()."'";

        $result = $this->_em->createQuery($query)->getOneOrNullResult();
        if($result) {
            return $result;
        }

        // Try to find the prev episode by changing the season
        $idAnime = $season->getIdAnime();

        $query = "SELECT ase
                FROM
                    LoopAnime\ShowsBundle\Entity\AnimesSeasons ase
                WHERE
                    ase.season = '$lookUpSeason'
                    AND ase.idAnime = '$idAnime'";

        $LookSeason = $this->_em->createQuery($query)->getOneOrNullResult();

        if($LookSeason) {

            // If the next episode then its going to be the 1st else order by DESC and pick the first one
            $query = "SELECT ae, ase.season
                FROM
                    LoopAnime\ShowsBundle\Entity\AnimesEpisodes ae
                    JOIN ae.animesSeasons ase
                WHERE
                    ".($nextEpisode? "ae.episode = '1' AND ": "")."
                    ae.idSeason = '".$season->getId()."'".
                    ($nextEpisode? "" : "
                    ORDER BY ae.episode DESC");
            $result = $this->_em->createQuery($query)->setMaxResults(1)->getOneOrNullResult();
            if($result) {
                return $result;
            }

        }

        return false;
    }

    public function getRecentEpisodes($getResults = true)
    {
        $q = $this->createQueryBuilder('ae')
                    ->select('ae')
                    ->where('ae.airDate <= CURRENT_TIMESTAMP()')
                    ->orderBy('ae.airDate','DESC')
                    ->getQuery();
        if($getResults) {
            return $q->getResult();
        } else {
            return $q;
        }
    }

    public function getUserRecentsEpisodes(Users $user, $getResults = true) {

        $userId = $user->getId();
        $userPreferences = $user->getPreferences();
        $order = "ASC";
        if($userPreferences !== null) {
            $order = $userPreferences->getTrackEpisodesSort();
        }

        $q = $this->createQueryBuilder('ae')
            ->select('ae')
            ->join('ae.animesSeasons','ase')
            ->join('ase.animes','a')
            ->join('a.userFavorites','uf')
            ->where('uf.idUser = :idUser')
            ->orderBy('ase.season',$order)
            ->addOrderBy('ae.episode',$order)
            ->setParameter('idUser',$userId)
            ->getQuery();

        if($getResults)
            return $q->getResult();
        else
            return $q;
    }

    public function getUserFutureEpisodes(Users $user, $getResults = true)
    {
        $userId = $user->getId();
        $q = $this->createQueryBuilder('ae')
            ->select('ae')
            ->join('ae.animesSeasons','ase')
            ->join('ase.animes','a')
            ->join('a.userFavorites','uf')
            ->where('uf.idUser = :idUser')
            ->orderBy('ae.airDate','ASC')
            ->setParameter('idUser',$userId);

        if($user->getPreferences() !== null) {
            if($user->getPreferences()->getFutureListSpecials())
                $q->andWhere('ase.season > 0');
        }

        $q = $q->getQuery();

        if($getResults)
            return $q->getResult();
        else
            return $q;
    }

    /**
     * @param Animes $anime
     * @return mixed
     */
    public function getTotEpisodes(Animes $anime)
    {
        $query = $this->createQueryBuilder("ae")
                ->select("COUNT(ae)")
                ->where("ae.idAnime = :idAnime")
                ->setParameter("idAnime", $anime->getId())
                ->getQuery();
        return $query->getSingleScalarResult();
    }

    /*public function getUserFutureEpisodes(User $user)
    {

        $user->getPreferences();

    }
$user_obj = new Users($_SESSION["user_info"]);

if($user_obj->getUserPreference("future_list_specials") == "0")
$where_clause .= " AND animes_seasons.season > 0";

$query = "SELECT
					animes_episodes.*
					FROM
						users_favorites
						JOIN animes USING(id_anime)
						JOIN animes_seasons USING(id_anime)
						JOIN animes_episodes USING(id_season)
					WHERE
						users_favorites.id_user = '".$id_user."'
						AND animes_episodes.air_date > NOW()
						AND $where_clause";
		case "future_episodes":
			$show_info = true;
			$order_by = "";
			if(!$user_obj->getIsLogged()) {
                include("templates/login_required.php");
                exit;
            }

			$episodes = $anime_obj->getUserFutureEpisodes( $user_obj->getUserInfo("id_user"), "animes_episodes.air_date > NOW()",  "0", "12", $order_by );
			break;
		case "to_see":
			$show_info = true;
			if($user_obj->getUserPreference("track_episodes_sort") == "")
                $order = "DESC";
            else
                $order = strtoupper($user_obj->getUserPreference("track_episodes_sort"));

			$order_by = "animes_seasons.season $order, animes_episodes.episode $order";
			if(!$user_obj->getIsLogged()) {
                include("templates/login_required.php");
                exit;
            }

			$where_clause .= "  AND (views.id_view IS NULL OR views.completed = 0)";

			$episodes = $anime_obj->getUser2SeeEpisodes( $user_obj->getUserInfo("id_user"), $where_clause, "0", "12", $order_by );

			break;
		default:
			$order_by = "";
			break;
    }*/

}
