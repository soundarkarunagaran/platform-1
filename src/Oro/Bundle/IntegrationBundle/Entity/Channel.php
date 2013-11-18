<?php

namespace Oro\Bundle\IntegrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table(name="oro_integration_channel")
 * @ORM\Entity
 * @Config(
 *  routeName="oro_integration_channel_index",
 *  defaultValues={
 *      "entity"={"label"="Integration Channel", "plural_label"="Integration Channels"},
 *      "security"={
 *          "type"="ACL",
 *          "group_name"=""
 *      }
 *  }
 * )
 */
class Channel
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="smallint", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    protected $type;

    /**
     * @var ArrayCollection|Transport[]
     *
     * @ORM\OneToMany(targetEntity="Oro\Bundle\IntegrationBundle\Entity\Transport",
     *     mappedBy="channel", cascade={"all"}, orphanRemoval=true
     * )
     */
    protected $transports;

    /**
     * @var ArrayCollection|Connector[]
     *
     * @ORM\OneToMany(targetEntity="Oro\Bundle\IntegrationBundle\Entity\Connector",
     *     mappedBy="channel", cascade={"all"}, orphanRemoval=true
     * )
     */
    protected $connectors;

    public function __construct()
    {
        $this->transports = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param Transport $transport
     *
     * @return $this
     */
    public function addTransport(Transport $transport)
    {
        if (!$this->transports->contains($transport)) {
            $this->transports->add($transport);
            $transport->setChannel($this);
        }

        return $this;
    }

    /**
     * @param Transport $transport
     *
     * @return $this
     */
    public function removeTransport(Transport $transport)
    {
        if ($this->transports->contains($transport)) {
            $this->transports->removeElement($transport);
        }

        return $this;
    }

    /**
     * @return ArrayCollection|Transport[]
     */
    public function getTransports()
    {
        return $this->transports;
    }

    /**
     * @param Connector $connector
     *
     * @return $this
     */
    public function addConnector(Connector $connector)
    {
        if (!$this->connectors->contains($connector)) {
            $this->connectors->add($connector);
            $connector->setChannel($this);
        }

        return $this;
    }

    /**
     * @param Connector $connector
     *
     * @return $this
     */
    public function removeConnector(Connector $connector)
    {
        if ($this->connectors->contains($connector)) {
            $this->connectors->removeElement($connector);
        }

        return $this;
    }

    /**
     * @return ArrayCollection|Connector[]
     */
    public function getConnectors()
    {
        return $this->connectors;
    }
}
