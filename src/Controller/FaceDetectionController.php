<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class FaceDetectionController extends AbstractController
{
    // called by JS to send face count to server
    #[Route('/api/face-detection', name: 'face_detection', methods: ['POST'])]
    public function store(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $faceCount = $data['face_count'] ?? 0;
        $timestamp = $data['timestamp'] ?? null;

        // store in session for now
        $request->getSession()->set('face_detections', [
            'count' => $faceCount,
            'timestamp' => $timestamp
        ]);

        return new JsonResponse([
            'status' => 'ok',
            'face_count' => $faceCount
        ]);
    }

    // admin page to see detections
    
#[Route('/mon-espace/face-detection', name: 'worker_face_detection')]
public function workerDetection(): Response
{
    return $this->render('face_detection/index.html.twig');
}



}