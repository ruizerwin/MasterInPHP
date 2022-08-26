<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Animal;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Session\Session;

use App\Form\AnimalType;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;


class AnimalController extends AbstractController
{
	
	public function validarEmail($email){
		
		$validator = Validation::createValidator();
		$errores = $validator->validate($email, [
			new Email()
		]);
		
		if(count($errores) != 0){
			echo "El email NO SE HA VALIDADO correctamente <br/>";
			
			foreach($errores as $error){
				echo $error->getMessage()."<br/>";
			}
			
		}else{
			echo "El email HA SIDO validado correctamente";
		}
		
		die();
	}
	
	public function crearAnimal(Request $request){
		$animal = new Animal();
		$form = $this->createForm(AnimalType::class, $animal);
						
		$form->handleRequest($request);
		
		if($form->isSubmitted() && $form->isValid()){
			$em = $this->getDoctrine()->getManager();
			$em->persist($animal);
			$em->flush();
			
			// Sesión flash
			$session = new Session();
			//$session->start();
			$session->getFlashBag()->add('message', 'Animal creado');
			
			return $this->redirectToRoute('crear_animal');
		}
		
		return $this->render('animal/crear-animal.html.twig',[
			'form' => $form->createView()
		]);
	}

    public function index()
    {
		$em = $this->getDoctrine()->getManager();
		$animal_repo = $this->getDoctrine()->getRepository(Animal::class);
		
		$animales = $animal_repo->findAll();
		
		$animal = $animal_repo->findBy([
			'raza' => 'africana'
		], [
			'id' => 'DESC'
		]);
		
		//var_dump($animal);
		
		// Query builder
		$qb = $animal_repo->createQueryBuilder('a')
						  //->andWhere("a.raza = :raza")
						  //->setParameter('raza', 'americana')
						  ->orderBy('a.id', 'DESC')
						  ->getQuery();
		$resultset = $qb->execute();
		
		// DQL 
		$dql = "SELECT a FROM App\Entity\Animal a ORDER BY a.id DESC";
		$query = $em->createQuery($dql);
		$resultset = $query->execute();
		
		// SQL
		$connection = $this->getDoctrine()->getConnection();
		$sql = 'SELECT * FROM animales ORDER BY id DESC';
		$prepare = $connection->prepare($sql);
		$prepare->execute();
		$resultset = $prepare->fetchAll();
		
		//var_dump($resultset);
		
		// Repositorio
		$animals = $animal_repo->getAnimalsOrderId('DESC');
		var_dump($animals);
		
		
        return $this->render('animal/index.html.twig', [
            'controller_name' => 'AnimalController',
			'animales' => $animales
        ]);
    }
	
	public function save(){
		// Guardar en una tabla de la bd
		
		// Cargo el em
		$entityManager = $this->getDoctrine()->getManager();
		
		// Creo el objeto y le doy valores
		$animal = new Animal();
		$animal->setTipo('Avestruz');
		$animal->setColor('verde');
		$animal->setRaza('africana');
		
		// Guardar objeto en doctrine
		$entityManager->persist($animal);
		
		// Volcar datos en la tabla / guardar en la bd
		$entityManager->flush();
		
		return new Response('El animal guardado tiene el id: '.$animal->getId());
	}
	
	public function animal(Animal $animal){
		
		/*
		// Cargar repositorio
		$animal_repo = $this->getDoctrine()->getRepository(Animal::class);
		
		// Consulta
		$animal = $animal_repo->find($id);
		 */
		
		// Comprobar si el resultado es correcto
		if(!$animal){
			$message = 'El animal no existe';
		}else{
			$message = 'Tu animal elegido es: '.$animal->getTipo().' - '.$animal->getRaza();
		}
		
		return new Response($message);
	}
	
	public function update($id){
		// Cargar doctrine
		$doctrine = $this->getDoctrine();
		
		// Cargar entityManager
		$em = $doctrine->getManager();
		
		// Cargar repo Animal
		$animal_repo = $em->getRepository(Animal::class);
				
		// Find para conseguir objeto
		$animal = $animal_repo->find($id);
		
		// Comprobar si el objeto me llega
		if(!$animal){
			$message = 'El animal no existe en la bbdd';
		}else{
		
			// Asignarle los valores al objeto
			$animal->setTipo("Perro $id");
			$animal->setColor('rojo');
		
			// Persistir en doctrine
			$em->persist($animal);
		
			// Guardar en la bd
			$em->flush();
			
			$message = 'Has actualizado el animal '.$animal->getId();
		}
		
		// Respuesta
		return new Response($message);
	}
	
	public function delete(Animal $animal){
		$em = $this->getDoctrine()->getManager();
		
		if($animal && is_object($animal)){
			$em->remove($animal);
			$em->flush();

			$message="Animal borrado correctamente";
		}else{
			$message="Animal no encontrado";
		}
		return new Response($message);
	}
	
}
