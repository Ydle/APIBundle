<?php
/*
  This file is part of Ydle.

    Ydle is free software: you can redistribute it and/or modify
    it under the terms of the GNU  Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Ydle is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU  Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with Ydle.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Ydle\APIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;


use Ydle\IhmBundle\Entity\NodeData;
use Ydle\RoomBundle\Entity\Room;

class DefaultController extends Controller
{
    /**
     * Expose rooms list
     * requested by url /api/rooms
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
                   "is_active" => $room->getIsActive(),
                   "description" => $room->getDescription(),
                   "type" => $room->getType()->getName(),
                   "sensors" => $this->get("ydle.nodes.manager")->countSensorsByRoom($room) 
            );
        }

        $this->get('ydle.logger')->log('info', 'Rooms list requested by '.$request->getClientIp() , 'api');
        
        return new JsonResponse(array('code' => 0, 'result' => $json));         
    }

    /**
     * add a room through the api
     * requested by url : /api/room
     * params accepted are : name, is_active, description, type_id
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws type
     */
    public function addRoomAction(Request $request)
    {
        $name = $request->get('name');
        $active  = $request->get('is_active');
        $description = $request->get('description');
        $typeId = $request->get('type_id');
        
        if(empty($name)){ 
            return new JsonResponse(array('code' => 2, 'result' => 'Name required'));
        }
        
        if(empty($typeId)){ 
            return new JsonResponse(array('code' => 2, 'result' => 'Type ID required'));
        }
        
        if(!$type = $this->get('ydle.roomtypes.manager')->getRepository()->find($typeId)){
            return new JsonResponse(array('code' => 2, 'result' => 'Wrong type id'));
        }

        $room = new Room();
        if(!is_null($active)) { $room->setIsActive($active); }
        if(!is_null($description)) { $room->setDescription($description); }
        $room->setName($name);
        $room->setType($type);

        $em = $this->getDoctrine()->getManager();
        $em->persist($room);
        $em->flush();
        
        if($room->getId()){
            $this->get('ydle.logger')->log('info', 'Room #'.$room->getId().' created from '.$request->getClientIp() , 'api');
            return new JsonResponse(array('code' => 0, 'result' => 'Room created with id : '.$room->getId()));
        } else {
            $this->get('ydle.logger')->log('info', 'Failed to create room from '.$request->getClientIp() , 'api');
            return new JsonResponse(array('code' => 4, 'result' => 'Cannot create room'));
        }
    }

    /**
     * Modify a room 
     * url used : /api/room/{id}
     * params accepted are : name, is_active, description, type_id, id
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws type
     */
    public function editRoomAction(Request $request)
    { 
        $id = $request->get('id');
        $name = $request->get('name');
        $active = $request->get('is_active');
        $description = $request->get('description');
        $typeId = $request->get('type_id');
                                                                                                 
        if(empty($id)){ return new JsonResponse(array('code' => 2, 'result' => 'Id required')); }
        if(empty($name)){ return new JsonResponse(array('code' => 2, 'result' => 'Name required')); }
        if(empty($typeId)){ return new JsonResponse(array('code' => 2, 'result' => 'Type id required')); } 

        if(!$room = $this->get("ydle.rooms.manager")->getRepository()->find($id)){ 
            return new JsonResponse(array('code' => 2, 'result' => 'Wrong id'));
        }
        
        if(!$type = $this->get('ydle.roomtypes.manager')->getRepository()->find($typeId)){
            return new JsonResponse(array('code' => 2, 'result' => 'Wrong type id'));
        }
                                   
        if(!is_null($active)) { $room->setIsActive($active); }
        if(!is_null($description)) { $room->setDescription($description); }
        $room->setName($name);
        $room->setType($type);

        $em = $this->getDoctrine()->getManager();
        $em->persist($room);
        $em->flush(); 
        
        $this->get('ydle.logger')->log('info', 'Room #'.$room->getId().' modified from '.$request->getClientIp() , 'api');
        return new JsonResponse(array('code' => 0, 'result' => 'Room modified'));
    }

    /**
     * Delete a room 
     * url used : /api/room/{id}
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws type
     */
    public function deleteRoomAction(Request $request)
    {
        $id = $request->get('id');
        if(!$room = $this->get("ydle.rooms.manager")->getRepository()->find($id)){
            return new JsonResponse(array('code' => 2, 'result' => 'Wrong id'));
        }
        $nodes = $this->get("ydle.nodes.manager")->findSensorsByRoom($room); 
        if(sizeof($nodes) > 0){
            return new JsonResponse(array('code' => 4, 'result' => 'Cannot a room associated to a node'));
        }

        $em = $this->getDoctrine()->getManager();                                                                         
        $em->remove($room);
        $em->flush();
        if($room->getId()){
            $this->get('ydle.logger')->log('info', 'Tried to delete room #'.$room->getId().' from '.$request->getClientIp() , 'api');
            return new JsonResponse(array('code' => 4, 'result' => 'failed to delete room'));
        } else {
            $this->get('ydle.logger')->log('info', 'Delete room #'.$id.' from '.$request->getClientIp() , 'api'); 
            return new JsonResponse(array('code' => 0, 'result' => 'room #'.$id.' deleted'));
        }
    }
    
    /**
     * Expose Room details 
     * url used : /api/room/{id}
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws type
     */
    public function roomAction(Request $request)
    {
        $id = $request->get('id');
        if(!$room = $this->get("ydle.rooms.manager")->getRepository()->find($id)){ 
            return new JsonResponse(array('code' => 2, 'result' => 'Wrong id'));
        }

        $sensorsData = array();
        // Load sensors from the room
        $nodes = $this->get("ydle.nodes.manager")->findSensorsByRoom($room); 
        foreach ($nodes as $node) { 
            foreach ($node->getTypes() as $capteur) { 
                $sensorsData[] = array(
                  "id" =>  $capteur->getId(),
                  "name" =>  $capteur->getName(),
                  "description" =>  $capteur->getDescription(),
                  "unit" =>  $capteur->getUnit(),
                  "is_active" =>  $capteur->getIsActive(),
                  // TODO current data
                   "current" => "10"
                );
            }
        }

        $json = array(
            "id" => $room->getId(),
            "name" => $room->getName(),
            "description" => $room->getDescription(),
            "is_active" => $room->getIsActive(),
            "type" => $room->getType()->getName(),
            "sensors" => $sensorsData
         );

        $this->get('ydle.logger')->log('info', 'Details for room #'.$request->get('id').' from '.$request->getClientIp() , 'api');
        return new JsonResponse(array('code' => 0, 'result' => $json));
    }
    
    /**
     * Save data from a node 
     * url used : /api/node/data
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
            return new JsonResponse(array('code' => 2, 'result' => 'Wrong node code'));
        }
        
        if(!$type = $this->get("ydle.sensor_types.manager")->getRepository()->findOneBy(array('id' => $typeId))){
            return new JsonResponse(array('code' => 2, 'result' => 'Wrong type'));
        }
        
        if(empty($data)){
            return new JsonResponse(array('code' => 2, 'result' => 'No data sent'));    
        }
        
        $nodeData = new NodeData();
        $nodeData->setNode($node);
        $nodeData->setData($data);
        $nodeData->setType($type);
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($nodeData);
        $em->flush();
        
        $this->get('ydle.logger')->log('data', 'Data received from node #'.$sender.' : '.$data, 'node');
            
        return new JsonResponse(array('code' => 0, 'result' => 'data sent'));
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

        $this->get('ydle.logger')->log('info', 'get types room from '.$request->getClientIp() , 'api');
        
        return new JsonResponse(array('code' => 0, 'result' => $json));         
    }

    /**
    * Expose node types
    * 
    * @param Request $request
    * @return JsonResponse
    */
    function nodeTypesAction(Request $request)
    {
        $types = $this->get('ydle.sensortypes.manager')->findAllByName();
        $json = array();
        
        foreach($types as $type){
            $json[] = $type->toArray();
        }

        $this->get('ydle.logger')->log('info', 'get types node from '.$request->getClientIp() , 'api');
        
        return new JsonResponse(array('code' => 0, 'result' => $json));         
    }
    
    /**
     * Expose logs to the master
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    function addLogAction(Request $request)
    {        
        $message = $request->get('message');
        $level   = $request->get('level');
        
        if(empty($message)){ return new JsonResponse(array('code' => 2, 'result' => 'Message required')); }
        
        $log = $this->get('ydle.logger')->log('log', $message, 'master');
        if(!is_object($log) || !$log->getId()){          
            return new JsonResponse(array('code' => 4, 'result' => 'log error'));
        } else {        
            return new JsonResponse(array('code' => 0, 'result' => 'log ok')); 
        }
    }
    
    /**
     * Expose array of logs to the master
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    function addLogsAction(Request $request)
    {        
        $logs = json_decode($request->getContent(), true);
        if(empty($logs)){
            return new JsonResponse(array('code' => 2, 'result' => 'empty logs'));       
        }
        
        $cpt = 0;
        foreach($logs as $log){
            $message = $log['message'];
            $level = $log['level'];
            $object = $this->get('ydle.logger')->log('log', $message, 'master');
            if(is_object($object) && $object->getId()){ $cpt++; }
        }
        
        if(count($logs) != $cpt) {          
            return new JsonResponse(array('code' => 4, 'result' => (count($logs) - $cpt).' logs not created'));
        } else {        
            return new JsonResponse(array('code' => 0, 'result' => $cpt.' logs created')); 
        }
        
    }
}
