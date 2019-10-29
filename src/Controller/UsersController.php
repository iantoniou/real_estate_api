<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\EntityMerger;
use App\Repository\UserRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UsersController extends AbstractFOSRestController
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var EntityMerger
     */
    private $entityMerger;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;
    

    /**
     * @param UserRepository $userRepository
     * @param EntityMerger $entityMerger
     * @param UserPasswordEncoderInterface $passwordEncoder
     */
    public function __construct(UserRepository $userRepository, EntityMerger $entityMerger, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->userRepository = $userRepository;
        $this->entityMerger = $entityMerger;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @Rest\View()
     * @Rest\Get("/users/{theUser}", name="get_user")
     * @SWG\Get(
     *      tags={"User"},
     *      summary="Get the user",
     *      consumes={"application/json"},
     *      produces={"application/json"},
     *      @SWG\Response(response="200", description="Returned when user found"),
     *      @SWG\Response(response="404", description="Returned when user not found")
     * )
     * 
     * @param User|null $theUser
     * @return User|null
     */
    public function getTheUser(?User $theUser)
    {
        if(null === $theUser) {
            return $this->view($theUser, Response::HTTP_OK);
        }

        return $theUser;
    }

    /**
     * @Rest\View()
     * @Rest\Get("/users", name="get_users")
     * @SWG\Get(
     *      tags={"Users"},
     *      summary="Get the all users",
     *      consumes={"application/json"},
     *      produces={"application/json"},
     *      @SWG\Response(response="200", description="Returned when all users are found"),
     *      @SWG\Response(response="404", description="Returned when users are not found")
     * )
     * 
     */
    public function getUsers()
    {
        $users = $this->userRepository->findAll();

        return $this->view($users, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/users", name="post_users")
     * @ParamConverter(
     *      "user",
     *      converter="fos_rest.request_body"
     * )
     * 
     * @SWG\Post(
     *     tags={"User"},
     *     summary="Add a new user resource",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Response(response="200", description="Returned when successful"),
     *     @SWG\Response(response="404", description="Returned when user cannot be created")
     * )
     *
     * @param User $user
     * @return User
     *
     */
    public function postUser(User $user)
    {
        $this->encodePassword($user);
        $this->persistUser($user);

        return $user;
    }


    /**
     * @Rest\View()
     * @Rest\Put("/users/{theUser}", name="put_user")
     * @ParamConverter(
     *      "modifiedUser",
     *      converter="fos_rest.request_body"
     * )
     * @SWG\Put(
     *      tags={"User"},
     *      summary="Edit the user",
     *      consumes={"application/json"},
     *      produces={"application/json"},
     *      @SWG\Response(response="200", description="Returned when user modified"),
     *      @SWG\Response(response="404", description="Returned when user cannot be modified")
     * )
     * 
     *  @param User|null $theUser
     * @param User $modifiedUser
     * @return User|null
     */
    public function putUser(?User $theUser, User $modifiedUser)
    {
        if(null === $theUser) {
            throw new NotFoundHttpException();
        }

        if (empty($modifiedUser->getPassword())) {
            $modifiedUser->setPassword(null);
        }

        $this->entityMerger->merge($theUser, $modifiedUser);

        $this->persistUser($theUser);

        return $theUser;

    }

    /**
     * @Rest\View()
     * @Rest\Delete("/users/{theUser}", name="delete_user")
     * @SWG\Delete(
     *      tags={"User"},
     *      summary="Delete the user",
     *      consumes={"application/json"},
     *      produces={"application/json"},
     *      @SWG\Response(response="200", description="Returned when user deleted"),
     *      @SWG\Response(response="404", description="Returned when user cannot be deleted")
     * )
     * 
     * @param User|null $theUser
     * @return \FOS\RestBundle\View\View
     */
    public function deleteUser(?User $theUser)
    {
        if(null === $theUser) {
            return $this->view(null, 404);
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($theUser);
        $manager->flush();
    }

    protected function persistUser(User $user): void
    {
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($user);
        $manager->flush();
    }

     /**
     * @param User $user
     */
    protected function encodePassword(User $user): void
    {
        $user->setPassword(
            $this->passwordEncoder->encodePassword(
                $user,
                $user->getPassword()
            )
        );
    }
}
