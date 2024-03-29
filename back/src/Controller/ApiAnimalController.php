<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Form\AnimalType;
use Psr\Log\LoggerInterface;
use App\Repository\AnimalRepository;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;


class ApiAnimalController extends AbstractController
{
    /** 
     * @Route("/api/animal", name= "api_animal_get", methods={"GET"})
     */
    public function getAnimals(AnimalRepository $animalRepository): Response
    {

        return $this->json($animalRepository->findAll(), 200, []);
    }

    /** 
     * @Route("/api/animal/{id}", name= "api_animal_get_by_id", methods={"GET"})
     */
    public function getOneAnimal(int $id, AnimalRepository $animalRepository): Response
    {
        return $this->json($animalRepository->find($id), 200, []);
    }

    /** 
     * @Route("/api/animal", name= "api_animal_post", methods={"POST"})
     */

    public function postAnimal(Request $request, LoggerInterface $logger, EntityManagerInterface $em)
    {
        $animal = new Animal();
        $form = $this->createForm(AnimalType::class, $animal);

        //$logger->info($request->getContent());
        $data = json_decode($request->getContent(), true);
        $form->submit($data);


        if ($form->isSubmitted() && $form->isValid()) {
            $logger->info('Validated');
            $animal = $form->getData();
            $em->persist($animal);
            $em->flush();
        } else {
            $logger->info('NOT validated');
            $errors = $this->getErrorsFromForm($form);

            foreach ($errors as $error) {
                $logger->info($error);
            }

            $data = [
                'type' => 'validation_error',
                'title' => 'There was a validation error',
                'errors' => $errors
            ];
            return new JsonResponse($data, 400);
        }

        return new JsonResponse('Saved new product with id ' . $animal->getId(), 200);
    }

    private function getErrorsFromForm(FormInterface $form)
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromForm($childForm)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }
        return $errors;
    }


    /** 
     * @Route("/api/animal/{id}", name= "api_animal_edit", methods={"PUT"})
     */

    public function updateAnimal(Animal $animal, Request $request, EntityManagerInterface $em, LoggerInterface $logger)
    {
        $logger->info('IM HERE');
        $data = json_decode($request->getContent(), true);
        var_dump($data);

        if (!$animal) {
            $animal = new Animal();
        }

        $form = $this->createForm(AnimalType::class, $animal);
        $form->handleRequest($request);

        $logger->info($request->getContent());
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $logger->info('Validated');
            $animal = $form->getData();
            var_dump($animal);
            $em->persist($animal);
            $em->flush();
        } else {
            $logger->info('NOT validated');
            $errors = $this->getErrorsFromForm($form);
            foreach ($errors as $error) {
                $logger->info($error);
            }
            $data = [
                'type' => 'validation_error',
                'title' => 'There was a validation error',
                'errors' => $errors
            ];
            return new JsonResponse($data, 400);
        }
        return new JsonResponse('OK', 200);
    }

    /** 
     * @Route("/api/animal/{id}", name= "api_animal_delete", methods={"DELETE"})
     */

    public function deleteAnimal(int $id, EntityManagerInterface $em)
    {
        $animal = $em->getRepository(Animal::class)->find($id);

        if (!$animal) {
            throw $this->createNotFoundException(
                'Pas d\'animal connu avec l\'id ' . $id
            );
        }

        $em->remove($animal);
        $em->flush();

        // return $this->json($animal, 204, []);

        return new Response(null, 204);
    }
}
