<?php
namespace App\Controller;

use App\Entity\Label;
use App\Entity\Todo;
use App\Repository\LabelRepository;
use App\Repository\TodoRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\utils\controllerHelper;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Exception;


class ApiController extends AbstractController
{
    use controllerHelper;
    /**
     * @var ObjectManager
     */
    private $em;
    /**
     * @var TodoRepository
     */
    private $todorepo;
    /**
     * @var LabelRepository
     */
    private $labelrepo;
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
        $this->labelrepo = $this->em->getRepository(Label::class);
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
            $label = $this->isLabeled(intval($params['label']), $this->labelrepo);
            $todo = new Todo($params, $label);
            $this->em->persist($todo);
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
            $label = $this->isLabeled(intval($params['label']), $this->labelrepo);
            $todo->hydrate($params, $label);
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
    public function readTodo(): JsonResponse
    {
        try {
            $all = $this->todorepo->findAll();
            $today = date_create(date('Y-m-d'));
            foreach ($all as $k => $v) {
                switch (true) {
                    case $v->getDate() > $today:
                        $v->setEtat('bon');
                        break;
                    case $v->getDate() < $today:
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
            return new JsonResponse($res);
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
//  LABEL
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
    public function addLabel(Request $req): JsonResponse
    {
        try {
            $params = $req->request->all();
            $label = new Label($params);
            $this->em->persist($label);
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
    public function editLabel(int $id, Request $req): JsonResponse
    {
        try {
            $label = $this->labelrepo->find($id);
            $params = $req->request->all();
            $label->hydrate($params);
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
    public function readLabel(): JsonResponse
    {
        try {
            $all = $this->labelrepo->findAll();
            $res = $this->serializer->serialize($all, 'json');
        }
        catch (Exception $e) {
            $res = $e->getMessage();
        } finally {
            return new JsonResponse($res);
        }
    }
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
    public function removeLabel(int $id): JsonResponse
    {
        try {
            $label = $this->labelrepo->find($id);
            $this->em->remove($label);
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