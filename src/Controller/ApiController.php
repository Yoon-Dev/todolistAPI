<?php
namespace App\Controller;

use App\Entity\Label;
use App\Entity\Todo;
use App\Repository\LabelRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\utils\controllerHelper;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;


class ApiController extends AbstractController
{
    use controllerHelper;
    /**
     * @var ObjectManager
     */
    private $em;
    /*
     * @var ObjectManger
     */
    private $todorepo;
    /*
     * @var ObjectManger
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
    public function addTodo(Request $req): Response
    {
        $params = $req->request->all();
        $label = $this->isLabeled(intval($params['label']), $this->labelrepo);
        $todo = new Todo($params, $label);
        $this->em->persist($todo);
        $this->em->flush();
        return new Response('Add Todo');
    }
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
    public function editTodo(int $id, Request $req): Response
    {
        $todo = $this->todorepo->find($id);
        $params = $req->request->all();
        $label = $this->isLabeled(intval($params['label']), $this->labelrepo);
        $todo->hydrate($params, $label);
        $this->em->flush();
        return new Response('Edit Todo');
    }
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
    public function readTodo(): Response
    {
        $all = $this->todorepo->findAll();
        $today = date_create(date('Y-m-d'));
        foreach ($all as $k => $v){
            switch(true){
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
//        var_dump($all);
        var_dump($this->serializer->serialize($all, 'json'));
        return new Response($this->serializer->serialize($all, 'json'));
    }
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
//°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°°
// END
}