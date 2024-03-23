<?php

/**
 * Exports calendar events
 */
class iCalExporter {

	/**
	 * @var Event
	 */
	protected $eventTimes;

	/**
	 * @var string
	 */
	protected $exportString;

	public function __construct( $eventTimes ) {
		$this->eventTimes = $eventTimes;
		$this->export = '';
	}

	public function export() {
		if ( empty( $this->exportString ) ) {
			$this->buildExport();
		}
		return $this->exportString;
	}

	protected function buildExport() {
		global $HOST;
		$this->append( 'BEGIN:VCALENDAR' );
		$this->append( 'VERSION:2.0' );
		$this->append( "PRODID:-//$HOST//NONSGML shiftcal v2.0//EN" );
		foreach ($this->eventTimes as $eventTime) {
			$this->buildEventTime($eventTime);
		}
		$this->append( 'END:VCALENDAR' );
	}

	protected function buildEventTime($eventTime) {
		$this->append( 'BEGIN:VEVENT' );
		$eventTimeId = $eventTime->getPkid();
		$details = $eventTime->toEventSummaryArray();
		$this->append( "UID:event-{$details['id']}-$eventTimeId@$HOST" );

		$modifiedString = $this->formatTime(
			$eventTime->getModified()->format( 'U' )
		);
		$this->append( "DTSTAMP:$modifiedString" );
		if ( !empty( $details['email'] ) ) {
			$this->append( "ORGANIZER:mailto:{$details['email']}" );
		}

		$date = new DateTime( (string)$eventTime->getEventdate() );
		list( $hour, $minute, $second ) = explode( ':', $details['time'] );
		$date->setTime( $hour, $minute, $second );
		$startString = $this->formatTime( $date->getTimestamp() );
		$this->append( "DTSTART:$startString" );
		if ( !empty( $details['eventduration'] ) ) {
			$durString = "PT{$details['eventduration']}M";
		}
		else {
			// set duration of 1 hour, if not specified
			$durString = "PT60M";
		}
		$date->add( new DateInterval( $durString ) );
		$endString = $this->formatTime( $date->getTimestamp() );
		$this->append( "DTEND:$endString" );

		$location = $this->formatLocation( $details );
		if ( !empty( $location ) ) {
			$this->append( "LOCATION:$location" );
		}

		$this->append( "SUMMARY:{$details['title']}" );

		$description = $this->formatDescription( $details );
		$this->append( "DESCRIPTION:$description" );

		$this->append( 'END:VEVENT' );
	}

	protected function formatTime( $timestamp ) {
		$date = new DateTime( "@$timestamp", new DateTimeZone( 'UTC' ) );
		return $date->format( 'Ymd\THis\Z' );
	}

	protected function formatLocation( $details ) {
		$parts = array();
		if ( !empty( $details['venue'] ) ) {
			$parts[] = $details['venue'];
		}
		if ( !empty( $details['address'] ) ) {
			$parts[] = $details['address'];
		}
		$address = implode( ', ', $parts );
		if ( !empty( $details['locdetails'] ) ) {
			$address .= "  {$details['locdetails']}";
		}
		return $address;
	}

	protected function formatDescription( $details ) {
		$description = $details['details'];
		// if time details are set, append to the description
		if ( !empty( $details['timedetails'] ) ) {
			$description .= "\\n" . $details['timedetails'];
		}
		// if location details are set, append to the description
		if ( !empty( $details['locdetails'] ) ) {
			$description .= "\\n" . $details['locdetails'];
		}
		// append the event's share link to the description;
		// this should always be set, but test for prescence to be sure
		if ( !empty( $details['shareable'] ) ) {
			$description .= "\\n" . $details['shareable'];
		}
		return $description;
	}

	protected function append( $string ) {
		$string = preg_replace( "/[\r\n]+/", " ", $string );
		$string = wordwrap( $string, 74, " \r\n " );
		$this->exportString .= "$string\r\n";
	}
}
