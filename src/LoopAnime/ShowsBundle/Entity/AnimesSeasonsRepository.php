<?php

namespace LoopAnime\ShowsBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Animes_SeasonsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AnimesSeasonsRepository extends EntityRepository
{

    /**
     * @param $idAnime
     * @param bool $getResults
     * @return array|\Doctrine\ORM\Query
     */
    public function getSeasonsByAnime($idAnime, $getResults = true) {
        $query = "SELECT ase
                FROM
                    LoopAnime\ShowsBundle\Entity\AnimesSeasons ase
                    JOIN ase.animes a
                WHERE
                    a.id = '".$idAnime."'";
        if($getResults)
            return $this->_em->createQuery($query)->getResult();
        else
            return $this->_em->createQuery($query);
    }

    public function getSeasonById($idSeason, $getResults = true)
    {

        $query = "SELECT ase
                FROM
                    LoopAnime\ShowsBundle\Entity\AnimesSeasons ase
                WHERE
                    ase.id = '".$idSeason."'";
        if($getResults)
            return $this->_em->createQuery($query)->getResult();
        else
            return $this->_em->createQuery($query);

    }

}
