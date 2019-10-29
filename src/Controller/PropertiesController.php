<?php

namespace App\Controller;

use App\Entity\Property;
use App\Entity\EntityMerger;
use App\Repository\PropertyRepository;
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

class PropertiesController extends AbstractFOSRestController
{
    /**
     * @var PropertyRepository
     */
    private $propertyRepository;

    /**
     * @var EntityMerger;
     */
    private $entityMerger;

    /**
     * @param PropertyRepository $propertyRepository
     * @param EntityMerger $entityMerger
     */
    public function __construct(PropertyRepository $propertyRepository, EntityMerger $entityMerger)
    {
        $this->propertyRepository = $propertyRepository;
        $this->entityMerger = $entityMerger;
    }

    /**
     * @Rest\View()
     * @Rest\Get("/properties/{theProperty}", name="get_property")
     * @SWG\Get(
     *      tags={"Property"},
     *      summary="Get the property",
     *      consumes={"application/json"},
     *      produces={"application/json"},
     *         @SWG\Response(response="200", description="Returned when property found"),
     *         @SWG\Response(response="404", description="Returned when property not found")
     * )
     * 
     * @param Property|null $theProperty
     * @return Property|null
     */
    public function getProperty(?Property $theProperty)
    {
        if(null == $theProperty) {
            return $this->view($theProperty, Response::HTTP_OK);
        }

        return $theProperty;
    }

    /**
     * @Rest\View()
     * @Rest\Get("/properties", name="get_properties")
     * @SWG\Get(
     *      tags={"Properties"},
     *      summary="Get the all properties",
     *      consumes={"application/json"},
     *      produces={"application/json"},
     *      @SWG\Response(response="200", description="Returned when all properties are found"),
     *      @SWG\Response(response="404", description="Returned when properties are not found")
     * )
     * 
     */
    public function getProperties()
    {
        $properties = $this->propertyRepository->findAll();

        return $this->view($properties, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/properties", name="post_properties")
     * @ParamConverter(
     *      "property",
     *      converter="fos_rest.request_body"
     * )
     * 
     * @SWG\Post(
     *     tags={"Property"},
     *     summary="Add a new property resource",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Response(response="200", description="Returned when successful"),
     *     @SWG\Response(response="404", description="Returned when property cannot be created")
     * )
     *
     * @param Property $property
     * @return Property
     *
     */
    public function postProperty(Property $property)
    {
        $this->persistProperty($property);

        return $property;
    }

    /**
     * @Rest\View()
     * @Rest\Put("/properties/{theProperty}", name="put_property")
     * @ParamConverter(
     *      "modifiedProperty",
     *      converter="fos_rest.request_body"
     * )
     * @SWG\Put(
     *      tags={"Property"},
     *      summary="Edit the property",
     *      consumes={"application/json"},
     *      produces={"application/json"},
     *      @SWG\Response(response="200", description="Returned when property modified"),
     *      @SWG\Response(response="404", description="Returned when property cannot be modified")
     * )
     * 
     *  @param Property|null $theProperty
     * @param Property $modifiedProperty
     * @return Property|null
     */
    public function putUser(?Property $theProperty, Property $modifiedProperty)
    {
        if(null === $theProperty) {
            throw new NotFoundHttpException();
        }

        $this->entityMerger->merge($theProperty, $modifiedProperty);

        $this->persistProperty($theProperty);

        return $theProperty;

    }

    /**
     * @Rest\View()
     * @Rest\Delete("/properties/{theProperty}", name="delete_property")
     * @SWG\Delete(
     *      tags={"Property"},
     *      summary="Delete the property",
     *      consumes={"application/json"},
     *      produces={"application/json"},
     *      @SWG\Response(response="200", description="Returned when property deleted"),
     *      @SWG\Response(response="404", description="Returned when property cannot be deleted")
     * )
     * 
     * @param User|null $theProperty
     * @return \FOS\RestBundle\View\View
     */
    public function deleteUser(?Property $theProperty)
    {
        if(null === $theProperty) {
            return $this->view(null, 404);
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($theProperty);
        $manager->flush();
    }

    protected function persistProperty(Property $property): void
    {
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($property);
        $manager->flush();
    }
}
