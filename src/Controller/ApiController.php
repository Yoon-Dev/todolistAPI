<?php
namespace App\Controller;

use App\Entity\Todo;
use App\Repository\TodoRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Exception;


class ApiController extends AbstractController
{
    /**
     * @var ObjectManager
     */
    private $em;
    /**
     * @var TodoRepository
     */
    private $todorepo;
    /**
     * @var Serializer
     */
    private $serializer;
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->todorepo = $this->em->getRepository(Todo::class);
        $encoder = new JsonEncoder();
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getName();
            },
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);
        $this->serializer = new Serializer([$normalizer], [$encoder]);
    }
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
    public function addTodo(Request $req): JsonResponse
    {
        try {
            $params = $req->request->all();
            $todo = new Todo($params);
            $this->em->persist($todo);
            var_dump($todo);
            $this->em->flush();
            $res = true;
        }
        catch (Exception $e) {
            $res = $e->getMessage();
        } finally {
            return new JsonResponse($res);
        }
    }
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
    public function editTodo(int $id, Request $req): JsonResponse
    {
        try {
            $todo = $this->todorepo->find($id);
            $params = $req->request->all();
            $todo->hydrate($params);
            $this->em->flush();
            $res = true;
        }
        catch (Exception $e) {
            $res = $e->getMessage();
        } finally {
            return new JsonResponse($res);
        }
    }
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
    public function readTodo(): Response
    {
        try {
            $all = $this->todorepo->findAll();
            $today = date_create(date('Y-m-d'));
            foreach ($all as $k => $v) {
                switch (true) {
                    case date_create($v->getDate()) > $today:
                        $v->setEtat('bon');
                        break;
                    case date_create($v->getDate()) < $today:
                        $v->setEtat('retard');
                        break;
                    default:
                        $v->setEtat('today');
                        break;
                }
            }
            $res = $this->serializer->serialize($all, 'json');
        }
        catch (Exception $e) {
            $res = $e->getMessage();
        } finally {
            return new Response($res);
        }
    }
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
    public function removeTodo(int $id): JsonResponse
    {
        try {
            $todo = $this->todorepo->find($id);
            $this->em->remove($todo);
            $this->em->flush();
            $res = true;
        }
        catch (Exception $e) {
            $res = $e->getMessage();
        } finally {
            return new JsonResponse($res);
        }
    }
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
// END
}