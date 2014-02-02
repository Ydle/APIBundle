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
                   "capteurs" => $this->get("ydle.nodes.manager")->countSensorsByRoom($room) 
            );
        }

        $this->get('ydle.logger')->log('info', 'get Rooms' , 'api');
        
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
        $nodes = $this->get("ydle.nodes.manager")->findSensorsByRoom($room); 
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

        $this->get('ydle.logger')->log('info', 'get Room #'.$request->get('id') , 'api');
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
        $sender   = $request->get('sender');
        $typeId   = $request->get('type');
        $data     = $request->get('data');
        if(!$node = $this->get("ydle.nodes.manager")->getRepository()->findOneBy(array('code' => $sender))){
            return new JsonResponse(array('node' => 'ko'));
        }
        
        if(!$type = $this->get("ydle.sensor_types.manager")->getRepository()->findOneBy(array('id' => $typeId))){
          return new JsonResponse(array('node' => 'ko'));
        }
        
        if(!$request->isMethod('POST')){
            return new JsonResponse(array('error' => 'wrong access method'));
        }
        
        if(empty($data)){
            return new JsonResponse(array('error' => 'no data sent'));
        }
        
        $nodeData = new NodeData();
        $nodeData->setNode($node);
        $nodeData->setData($data);
        $nodeData->setType($type);
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($nodeData);
        $em->flush();
        
        $this->get('ydle.logger')->log('data', 'Data received from node #'.$sender.' : '.$data, 'node');
            
        return new JsonResponse(array('node' => 'ok'));
    }
    
    /**
    * Expose room types
    * 
    * @param Request $request
    * @return JsonResponse
    */
    function roomTypesAction(Request $request)
    {
        $types = $this->get('ydle.roomtypes.manager')->findAllByName();
        $json = array();
        
        foreach($types as $type){
            $json[] = $type->toArray();
        }

        $this->get('ydle.logger')->log('info', 'get types room' , 'api');
            
        return new JsonResponse(array('types' => $json));        
    }
    
    /**
     * Expose logs to the master
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    function addLogAction(Request $request)
    {
        if(!$request->isMethod('POST')){
            return new JsonResponse(array('error' => 'wrong access method'));
        }
        
        $message = $request->get('message');
        $level   = $request->get('level');
        
        $this->get('ydle.logger')->log('log', $message, 'master');
        
        return new JsonResponse(array('log' => 'ok'));
    }
    
    /**
     * Expose array of logs to the master
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    function addLogsAction(Request $request)
    {
        if(!$request->isMethod('POST')){
            return new JsonResponse(array('error' => 'wrong access method'));
        }
        
        $logs = json_decode($request->getContent(), true);
        
        //var_dump($logs);die;
        foreach($logs as $log){
            $message = $log['message'];
            $level = $log['level'];
            $this->get('ydle.logger')->log('log', $message, 'master');
        }
        
        return new JsonResponse(array('log' => '{ok:'.count($logs).'}'));
    }
}
