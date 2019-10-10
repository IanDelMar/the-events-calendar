<?php
/**
 * View: Day View Type separator
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events/views/v2/day/type-separator.php
 *
 * See more documentation about our views templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version TBD
 *
 */

use Tribe\Events\Views\V2\Utils;
use Tribe__Date_Utils as Dates;

$should_have_type_separator = Utils\Separators::should_have_type( $this->get( 'events' ), $event );

if ( ! $should_have_type_separator ) {
	return;
}
?>
<div class="tribe-events-calendar-day__type-separator">
	<span class="tribe-events-calendar-day__type-separator-text tribe-common-h7 tribe-common-h6--min-medium tribe-common-h--alt">
		<?php echo esc_html( $event->timeslot ); ?>
	</span>
</div>
