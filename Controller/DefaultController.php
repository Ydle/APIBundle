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
        $sender = $request->get('sender');
        $type   = $request->get('type');
        $data   = $request->get('data');
        if(!$node = $this->get("ydle.ihm.nodes.manager")->getRepository()->findOneBy(array('code' => $sender))){
            return new JsonResponse(array('node' => 'ko'));
        }
        
        if(!$request->isMethod('POST')){
            return new JsonResponse(array('error' => 'wrong access method'));
        }
        
        $nodeData = new NodeData();
        $nodeData->setNode($node);
        $nodeData->setData($data);
        $nodeData->setType((int)$type);
        
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
}
