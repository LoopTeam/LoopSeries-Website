<?php

namespace LoopAnime\ShowsBundle\Entity;

use Doctrine\ORM\EntityRepository;
use LoopAnime\CrawlersBundle\Services\hosters\Hosters;

/**
 * Animes_LinksRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AnimesLinksRepository extends EntityRepository
{

    public function getLinksByEpisode($idEpisode, $getResults = true)
    {
        $query = $this->createQueryBuilder('al')
            ->select('al')
            ->where('al.idEpisode = :idEpisode')
            ->setParameter('idEpisode',$idEpisode)
            ->getQuery();

        if($getResults)
            return $query->getResult();
        else
            return $query;
    }

    /**
     * @param Hosters $hoster
     * @param AnimesEpisodes $episode
     * @return integer|boolean
     */
    public function removeLinks(Hosters $hoster, AnimesEpisodes $episode)
    {
        $q = $this->_em
            ->createQuery('DELETE FROM LoopAnime\ShowsBundle\Entity\AnimesLinks al WHERE al.idEpisode = :idEpisode AND al.hoster = :hoster')
            ->setParameter('idEpisode', $episode->getId())
            ->setParameter('hoster', $hoster->getName())
            ;
        return $q->execute();
    }

}
