<?php

namespace App\EventListener;

use App\Repository\FormationRepository;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarListener implements EventSubscriberInterface
{
    public function __construct(
        private FormationRepository $formationRepository,
        private UrlGeneratorInterface $router
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendar): void
    {
        $start = $calendar->getStart();
        $end   = $calendar->getEnd();

        $formations = $this->formationRepository->createQueryBuilder('f')
            ->where('f.dateDebut IS NOT NULL')
            ->andWhere('f.dateDebut BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        foreach ($formations as $formation) {
            $event = new Event(
                $formation->getTitre(),
                $formation->getDateDebut(),
                $formation->getDateFin() ?? $formation->getDateDebut()
            );

            $event->setOptions([
                'backgroundColor' => '#6C63FF',
                'borderColor'     => '#9D6BFF',
                'textColor'       => '#ffffff',
                'url'             => $this->router->generate('client_formation_detail', [
                    'id' => $formation->getIdFormation(),
                ]),
            ]);

            $calendar->addEvent($event);
        }
    }
}
