<?php
namespace Manticora\CMSBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Manticora\CMSBundle\Entity\Route;
use Manticora\CMSBundle\Entity\Template;
use Manticora\CMSBundle\Entity\TemplateCategory;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Twig_Environment;

class CmsService
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @var CacheClearerInterface
     */
    private $cacheClearer;

    private $cacheDir;

    /**
     * Constructor.
     *
     * @param \Doctrine\ORM\EntityManager $em the Doctrine Entity Manager
     * @param \Twig_Environment $twig the Twig Environment
     */
    public function __construct(EntityManager $em, Twig_Environment $twig, CacheClearerInterface $cacheClearer, $cacheDir)
    {
        $this->em = $em;
        $this->twig = $twig;
        $this->cacheClearer = $cacheClearer;
        $this->cacheDir = $cacheDir;
    }

    public function removeRoute(Route $route) {
        $this->em->remove($route);
        $this->em->flush();
    }

    public function clearCache() {
        $this->twig->clearTemplateCache();
        $this->cacheClearer->clear($this->cacheDir);
    }

    /**
     * @param TemplateCategory $category
     * @return ArrayCollection
     */
    public function getTemplateList(TemplateCategory $category=null) {

        $criteria = array();
        if ($category) {
            $criteria = array('category'=>$category);
        }

        return $this->em->getRepository('ManticoraCMSBundle:Template')->findBy($criteria, array('name'=>'asc'));
    }


    /**
     * @param integer $id
     * @return Template
     */
    public function getTemplate($id) {
        return $this->em->getRepository('ManticoraCMSBundle:Template')->find($id);
    }

    /**
     * @return ArrayCollection
     */
    public function getCategories() {
        return $this->em->getRepository('ManticoraCMSBundle:TemplateCategory')->findAll();
    }

    /**
     * @param string $categoryName
     * @return TemplateCategory
     */
    public function getCategoryByName($categoryName) {
        return $this->em->getRepository('ManticoraCMSBundle:TemplateCategory')->findOneBy(array('name'=>$categoryName));
    }

    /**
     * @param TemplateCategory $categoryName
     * @return Template
     */
    public function createNewTemplate(TemplateCategory $category=null) {
        $template = new Template();
        if ($category) {
            $template->setCategory($category);
        }
        return $template;
    }

    public function renderTemplateFromString($twig_string, $params = array()) {
        return $this->twig->render($twig_string, $params);
    }

    public function renderTemplate($id, $params = array()) {
        if (!$template = $this->getTemplate($id)) {
            throw new Exception("Template $id not found");
        }

        return $this->renderTemplateFromString($template->getBody(), $params);
    }

    /**
     * @param Template $template
     */
    public function saveTemplate(Template $template) {
        $this->em->persist($template);
        $this->em->flush();
        $this->clearCache();
    }

}