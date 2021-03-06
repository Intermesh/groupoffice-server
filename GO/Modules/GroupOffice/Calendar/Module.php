<?php

namespace GO\Modules\GroupOffice\Calendar;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\Calendar\Controller\EventController;
use GO\Modules\GroupOffice\Calendar\Controller\CalendarController;
use GO\Modules\GroupOffice\Calendar\Controller\UserController;
use GO\Modules\GroupOffice\Calendar\Model\ICalendarHelper;
use GO\Modules\GroupOffice\Calendar\Model\Event;
use IFW\Web\Router;

class Module extends InstallableModule implements \IFW\Event\EventListenerInterface {	

	//TODO: implement
	public static $notifications = [
		'eventCreation', // A new event has been added to the users calendar // RSVP
		'eventChange', // An event the user is invited to was changed by the organizer
		'eventCancellation', // An even the user was invited to was cancelled
		'attendeeResponse', // An attendee the user has invited has responded to the event
		'eventAlarm', // An alarm set for an email is triggering
	];

	public static function defineEvents() {
		if(GO()->getModules()->has('GO\Modules\GroupOffice\Messages\Module')) {
			\GO\Modules\GroupOffice\Messages\Model\Attachment::on('newIcsAttachment', self::class, 'importICS');
		}
	}

	public static function importICS($blob) {
		try {
			$vobject = ICalendarHelper::read($blob);
		
			$event = Event::findByUUID((string)$vobject->VEVENT->UID);
			if(empty($event)) {

				\GO()->debug('EVENT IS SAVING: '.(string)$vobject->VEVENT->UID);
				$events = ICalendarHelper::fromVObject($vobject);
				foreach($events as $event) { // might include exception events
					$event->save();
				}
			} else {
				\GO()->debug('EVENT FOUND');
			}
		} catch(\Exception $e) {

			\GO()->debug('Failed to read icalendar data. '.$e);


		}
	}

	public static function defineWebRoutes(Router $router){
		
		$router->addRoutesFor(EventController::class)
			->get('event', 'store') // filterable by calendars
			->get('event/:calendarId/0', 'newInstance')
			->get('event/:calendarId/:eventId/download', 'download')
			->get('event/:calendarId/:eventId', 'read')
			->put('event/:calendarId/:eventId', 'update')
			//->put('event/:calendarId/:eventId/:recurrenceId', 'updateInstance')
			->post('event/:calendarId', 'create')
			//->delete('event/:calendarId/:eventId/:recurrenceId', 'deleteInstance');
			->delete('event/:calendarId/:eventId', 'delete');

		$router->addRoutesFor(CalendarController::class)
			->get('calendar', 'store')
			->get('calendar/0','newInstance')
			->get('calendar/:id','read')
			->put('calendar/:id', 'update')
			->post('calendar', 'create')
			->delete('calendar/:id', 'delete');

		$router->addRoutesFor(UserController::class)
			->get('account', 'store');
	}
	
	public function autoInstall() {
		return false;
	}
	
}
