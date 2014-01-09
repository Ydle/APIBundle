<?php

namespace Ydle\APIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;

use Ydle\IhmBundle\Entity\NodeData;

class DefaultController extends Controller
{
    /**
     * Expose rooms list
     * 
     * @Template()
     */
    public function roomsAction(Request $request)
    {
        $rooms = $this->get("ydle.rooms.manager")->findAllByName();
        foreach ($rooms as $room) {
            $json[] = array(
                   "id" => $room->getId(),
                   "name" => $room->getName(),
                   "active" => $room->getIsActive(),
                   "description" => $room->getDescription(),
                   "type" => $room->getType()->getName(),
                   // rechercher le nombre de capteurs dans la pi?ce
                   "capteurs" => $this->get("ydle.ihm.nodes.manager")->countSensorsByRoom($room) 
            );
        }
        return new JsonResponse(array('rooms' => $json));        
    }
    
    /**
     * Expose Room details
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws type
     */
    public function roomAction(Request $request)
    {
        if(!$room = $this->get("ydle.rooms.manager")->getRepository()->find($request->get('id'))){
            return new JsonResponse(array('room' => 'ko'));
        }

        $jsonSensor = array();
         //  rechercher les capteurs(name,type,currentValue,unit) de la pi?ce
        $nodes = $this->get("ydle.ihm.nodes.manager")->findSensorsByRoom($room); 
        foreach ($nodes as $node) { 
            foreach ($node->getTypes() as $capteur) { 
                $jsonSensor[]=array(
                  "id" =>  $capteur->getId(),
                  "name" =>  $capteur->getName(),
                  "description" =>  $capteur->getDescription(),
                  "unit" =>  $capteur->getUnit(),
                  "active" =>  $capteur->getIsActive(),
                  // TODO current data
                   "current" => "10"
                );
            }
        }


        $json = array(
            "id" => $room->getId(),
            "name" => $room->getName(),
            "description" => $room->getDescription(),
            "active" => $room->getIsActive(),
            "type" => $room->getType()->getName(),
            "capteurs" => $jsonSensor
         );
        return new JsonResponse(array('room' => $json));
    }
    
    /**
     * Save data from a node
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws type
     */
    function dataAction(Request $request)
    {
        if(!$node = $this->get("ydle.ihm.nodes.manager")->getRepository()->findOneBy(array('code' => $request->get('node')))){
            return new JsonResponse(array('node' => 'ko'));
        }
        $type = $request->get('type');
        $data = $request->get('data');
        
        $nodeData = new NodeData();
        $nodeData->setNode($node);
        $nodeData->setData($data);
        $nodeData->setType($type);
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($nodeData);
        $em->flush();
            
        return new JsonResponse(array('node' => 'ok'));
    }
}
