<?php

namespace ICAP\BlogBundle\Entity;

use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\BlogOptions;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Claroline\CoreBundle\Entity\Resource\AbstractResource;

/**
 * @ORM\Entity
 * @ORM\Table(name="icap__blog")
 * @ORM\Entity(repositoryClass="ICAP\BlogBundle\Repository\BlogRepository")
 */
class Blog extends AbstractResource
{
    /**
     * @var Post[]
     *
     * @ORM\OneToMany(targetEntity="ICAP\BlogBundle\Entity\Post", mappedBy="blog")
     * @ORM\OrderBy({"creationDate" = "ASC"})
     */
    protected $posts;

    /**
     * @var BlogOptions
     *
     * @ORM\OneToOne(targetEntity="BlogOptions", mappedBy="blog")
     */
    protected $options;

    public function __construct()
    {
        $this->posts  = new ArrayCollection();
    }

    /**
     * @param ArrayCollection $posts
     *
     * @return Blog
     */
    public function setPosts(ArrayCollection $posts)
    {
        $this->posts = $posts;

        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection|Post[]
     */
    public function getPosts()
    {
        return $this->posts;
    }

    /**
     * @param BlogOptions $options
     *
     * @return Blog
     */
    public function setOptions(BlogOptions $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return BlogOptions
     */
    public function getOptions()
    {
        return $this->options;
    }
}
