<?php

namespace Icap\BlogBundle\Controller;

use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Library\Resource\ResourceCollection;
use Icap\BlogBundle\Entity\Blog;
use Icap\BlogBundle\Entity\BlogOptions;
use Icap\BlogBundle\Entity\Post;
use Icap\BlogBundle\Entity\Statusable;
use Icap\BlogBundle\Exception\TooMuchResultException;
use Icap\BlogBundle\Form\BlogBannerType;
use Icap\BlogBundle\Form\BlogInfosType;
use Icap\BlogBundle\Form\BlogOptionsType;
use Icap\BlogBundle\Entity\Tag;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Pagerfanta\Pagerfanta;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlogController extends Controller
{
    /**
     * @Route("/{blogId}/{page}", name="icap_blog_view", requirements={"blogId" = "\d+", "page" = "\d+"}, defaults={"page" = 1})
     * @Route("/{blogId}/{filter}/{page}", name="icap_blog_view_filter", requirements={"blogId" = "\d+", "page" = "\d+"}, defaults={"page" = 1})
     * @ParamConverter("blog", class="IcapBlogBundle:Blog", options={"id" = "blogId"})
     * @Template()
     */
    public function viewAction(Blog $blog, $page, $filter = null)
    {
        $this->checkAccess("OPEN", $blog);

        $user = $this->get('security.context')->getToken()->getUser();

        $search = $this->getRequest()->get('search');
        if (null !== $search && '' !== $search) {
            return $this->redirect($this->generateUrl('icap_blog_view_search', array('blogId' => $blog->getId(), 'search' => $search)));
        }

        /** @var \Icap\BlogBundle\Repository\PostRepository $postRepository */
        $postRepository = $this->get('icap.blog.post_repository');

        $tag    = null;
        $author = null;
        $date   = null;

        if (null !== $filter) {
            $tag = $this->get('icap.blog.tag_repository')->findOneByName($filter);

            if (null === $tag) {
                $author = $this->getDoctrine()->getRepository('ClarolineCoreBundle:User')->findOneByUsername($filter);

                if (null === $author) {
                    $date = $filter;
                }
            }
        }

        /** @var \Doctrine\ORM\QueryBuilder $query */
        $query = $postRepository
            ->createQueryBuilder('post')
            ->andWhere('post.blog = :blogId')
        ;

        if (!$this->isUserGranted("EDIT", $blog)) {
            $query = $postRepository->filterByPublishPost($query);
        }

        $criterias = array(
            'tag'    => $tag,
            'author' => $author,
            'date'   => $date,
            'blogId' => $blog->getId()
        );

        $query = $postRepository->createCriteriaQueryBuilder($criterias, $query);

        $adapter = new DoctrineORMAdapter($query);
        $pager   = new PagerFanta($adapter);

        $pager->setMaxPerPage($blog->getOptions()->getPostPerPage());

        try {
            $pager->setCurrentPage($page);
        } catch (NotValidCurrentPageException $exception) {
            throw new NotFoundHttpException();
        }

        $bannerForm = $this->createForm(new BlogBannerType(), $blog->getOptions());

        return array(
            '_resource'     => $blog,
            '_resourceNode' => new ResourceCollection(array($blog->getResourceNode())),
            'bannerForm'    => $bannerForm->createView(),
            'user'          => $user,
            'pager'         => $pager,
            'archives'      => $this->getArchiveDatas($blog),
            'tag'           => $tag,
            'author'        => $author,
            'date'          => $date
        );
    }

    /**
     * @Route("/{blogId}/search/{search}/{page}", name="icap_blog_view_search", requirements={"blogId" = "\d+", "page" = "\d+"}, defaults={"page" = 1})
     * @ParamConverter("blog", class="IcapBlogBundle:Blog", options={"id" = "blogId"})
     * @Template()
     */
    public function viewSearchAction(Blog $blog, $page, $search)
    {
        $this->checkAccess("OPEN", $blog);

        $user = $this->get('security.context')->getToken()->getUser();

        /** @var \Icap\BlogBundle\Repository\PostRepository $postRepository */
        $postRepository = $this->get('icap.blog.post_repository');

        try {
            /** @var \Doctrine\ORM\QueryBuilder $query */
            $query = $postRepository->searchByBlog($blog, $search, false);

            if (!$this->isUserGranted("EDIT", $blog)) {
                $query
                    ->andWhere('post.publicationDate IS NOT NULL')
                    ->andWhere('post.status = :publishedStatus')
                    ->setParameter('publishedStatus', Statusable::STATUS_PUBLISHED)
                ;
            }

            $adapter = new DoctrineORMAdapter($query);
            $pager   = new PagerFanta($adapter);

            $pager
                ->setMaxPerPage($blog->getOptions()->getPostPerPage())
                ->setCurrentPage($page)
            ;
        } catch (NotValidCurrentPageException $exception) {
            throw new NotFoundHttpException();
        } catch (TooMuchResultException $exception) {
            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('icap_blog_post_search_too_much_result', array(), 'icap_blog'));
            $adapter = new ArrayAdapter(array());
            $pager   = new PagerFanta($adapter);

            $pager->setCurrentPage($page);
        }

        return array(
            '_resource' => $blog,
            'user'      => $user,
            'pager'     => $pager,
            'search'    => $search,
            'archives'  => $this->getArchiveDatas($blog)
        );
    }

    /**
     * @Route("/configure/{blogId}", name="icap_blog_configure", requirements={"blogId" = "\d+"})
     * @ParamConverter("blog", class="IcapBlogBundle:Blog", options={"id" = "blogId"})
     * @Template()
     */
    public function configureAction(Request $request, Blog $blog)
    {
        $this->checkAccess("EDIT", $blog);

        $blogOptions = $blog->getOptions();

        $form = $this->createForm(new BlogOptionsType(), $blogOptions);

        if ("POST" === $request->getMethod()) {
            $form->submit($request);
            if ($form->isValid()) {
                $entityManager = $this->getDoctrine()->getManager();
                $translator = $this->get('translator');
                $flashBag = $this->get('session')->getFlashBag();

                try {
                    $unitOfWork = $entityManager->getUnitOfWork();
                    $unitOfWork->computeChangeSets();
                    $changeSet = $unitOfWork->getEntityChangeSet($blogOptions);

                    $entityManager->persist($blogOptions);
                    $entityManager->flush();

                    $this->dispatchBlogConfigureEvent($blog, $blogOptions, $changeSet);

                    $flashBag->add('success', $translator->trans('icap_blog_post_configure_success', array(), 'icap_blog'));
                } catch (\Exception $exception) {
                    $flashBag->add('error', $translator->trans('icap_blog_post_configure_error', array(), 'icap_blog'));
                }

                return $this->redirect($this->generateUrl('icap_blog_configure', array('blogId' => $blog->getId())));
            }
        }

        return array(
            '_resource' => $blog,
            'form'      => $form->createView(),
            'archives'  => $this->getArchiveDatas($blog)
        );
    }

    /**
     * @Route("/edit/{blogId}", name="icap_blog_edit_infos", requirements={"blogId" = "\d+"})
     * @ParamConverter("blog", class="IcapBlogBundle:Blog", options={"id" = "blogId"})
     * @Template()
     */
    public function editAction(Request $request, Blog $blog)
    {
        $this->checkAccess("EDIT", $blog);

        $form = $this->createForm(new BlogInfosType(), $blog);

        if ("POST" === $request->getMethod()) {
            $form->submit($request);
            if ($form->isValid()) {
                $entityManager = $this->getDoctrine()->getManager();
                $translator = $this->get('translator');
                $flashBag = $this->get('session')->getFlashBag();

                try {
                    $unitOfWork = $entityManager->getUnitOfWork();
                    $unitOfWork->computeChangeSets();
                    $changeSet = $unitOfWork->getEntityChangeSet($blog);

                    $entityManager->persist($blog);
                    $entityManager->flush();

                    $this->dispatchBlogUpdateEvent($blog, $changeSet);

                    $flashBag->add('success', $translator->trans('icap_blog_edit_infos_success', array(), 'icap_blog'));
                } catch (\Exception $exception) {
                    $flashBag->add('error', $translator->trans('icap_blog_edit_infos_error', array(), 'icap_blog'));
                }

                return $this->redirect($this->generateUrl('icap_blog_view', array('blogId' => $blog->getId())));
            }
        }

        return array(
            '_resource' => $blog,
            'form'      => $form->createView(),
            'archives'  => $this->getArchiveDatas($blog)
        );
    }

    /**
     * @Route("/calendar/{blogId}", name="icap_blog_calendar_datas", requirements={"blogId" = "\d+"})
     * @ParamConverter("blog", class="IcapBlogBundle:Blog", options={"id" = "blogId"})
     */
    public function calendarDatas(Request $request, Blog $blog)
    {
        $requestParameters = $request->query->all();
        $startDate         = $requestParameters['start'];
        $endDate           = $requestParameters['end'];
        $calendarDatas     = array();

        /** @var \Icap\BlogBundle\Repository\PostRepository $postRepository */
        $postRepository = $this->getDoctrine()->getManager()->getRepository('IcapBlogBundle:Post');

        $posts = $postRepository->findCalendarDatas($blog, $startDate, $endDate);

        foreach ($posts as $post) {
            $calendarDatas[] = array(
                'id'    => '12',
                'start' => $post['publicationDate']->format('Y-m-d'),
                'title' => $post['nbPost'],
                'url'   => $this->generateUrl('icap_blog_view_filter', array('blogId' => $blog->getId(), 'filter' => $post['publicationDate']->format('d-m-Y')))
            );
        }

        $response = new JsonResponse($calendarDatas);

        return $response;
    }
}
