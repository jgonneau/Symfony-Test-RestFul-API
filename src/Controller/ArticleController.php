<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ArticleController extends FOSRestController
{
    private $articleRepository;
    private $em;

    public function __construct (ArticleRepository $articleRepository, EntityManagerInterface $em)
    {
        $this->articleRepository = $articleRepository;
        $this->em = $em;
    }

    /**
     * @Rest\View(serializerGroups={"article.user"})
     */
    public function getArticlesAction ()
    {
        if ( in_array("ROLE_ADMIN", $this->getUser()->getRoles()) ) {
            $articles = $this->articleRepository->findAll();
            return $this->view($articles);
        }
        else if ( $this->getUser()->getApiKey() ) {
            $articles = $this->articleRepository->findBy([
                "userId" => $this->getUser()->getId()
            ]);
            return $this->view($articles);
        }
        else {
            return $this->view('FORBIDDEN');
        }
    }

    /**
     * @Rest\View(serializerGroups={"article"})
     */
    public function getArticleAction(Article $article)
    {
        if ( in_array("ROLE_ADMIN", $this->getUser()->getRoles()) ) {
            $article_data = $this->articleRepository->find($article->getId());
            return $this->view($article_data);
        }
        else if ( $this->getUser()->getApiKey() ) {
            $article_data = $this->articleRepository->findOneBy([
                "userId" => $this->getUser()->getId(),
                "id" => $article->getId()
            ]);
            return $this->view($article_data);
        }
        else {
            return $this->view('FORBIDDEN');
        }
    }

    /**
     * @Rest\View(serializerGroups={"article"})
     * @Rest\Post("/articles")
     * @ParamConverter("article", converter="fos_rest.request_body")
     */
    public function postArticlesAction(Article $article, ValidatorInterface $validator)
    {

       // /** @var ConstraintViolationList $validationErrors */
       //$validationErrors = $validator->validate($user);
       //foreach ($validationErrors as $constraintViolation){/*
       //  // Your code here *
       //} * 

        if ( in_array("ROLE_ADMIN", $this->getUser()->getRoles()) ) {
            $article->setCreatedAt(new \DateTime());
            $this->em->persist($article);
            $this->em->flush();
            return $this->view($article);
        }
        else if ( $this->getUser()->getApiKey() ) {
            $article->setUserId($this->getUser());
            $article->setCreatedAt(new \DateTime());
            $this->em->persist($article);
            $this->em->flush();
            return $this->view($article);
        }
        else {
            return $this->view('FORBIDDEN');
        }

    }

    /**
     * @Rest\View(serializerGroups={"article"})  
     */
    public function putArticleAction(Request $request, int $id)
    {
        $article_data = $this->articleRepository->find($id);

        // $request->get()
        if ( $this->getUser()->getId() === $article_data->getUserId()->getId() || in_array("ROLE_ADMIN", $this->getUser()->getRoles()) ) {

            if ($nn = $request->get('name')) {
                $article_data->setName($nn);
            }

            if ($desc = $request->get('description')) {
                $article_data->setDescription($desc);
            }

            $this->em->persist($article_data);

            $this->em->flush();
            return $this->view($article_data);

        }
        else {
            return $this->view('FORBIDDEN');
        }
    }

    /**
     * @Rest\View(serializerGroups={"article"}) 
     */
    public function deleteArticleAction(Article $article)
    {
         if ( $this->getUser()->getId() === $article->getUserId()->getId() || in_array("ROLE_ADMIN", $this->getUser()->getRoles()) ) {

                    $this->em->remove($article);
                    $this->em->flush();
                    return $this->view($article);
         }
         else {
             return $this->view($article);
         }
    }

}
