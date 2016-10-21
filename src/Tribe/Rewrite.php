<?php
// Don't load directly
defined( 'WPINC' ) or die;

/**
 * Rewrite Configuration Class
 * Permalinks magic Happens over here!
 */
class Tribe__Events__Rewrite extends  Tribe__Rewrite {
	/**
	 * Static singleton variable
	 * @var self
	 */
	public static $instance;

	/**
	 * WP_Rewrite Instance
	 * @var WP_Rewrite
	 */
	public $rewrite;

	/**
	 * Rewrite rules Holder
	 * @var array
	 */
	public $rules = array();

	/**
	 * Base slugs for rewrite urls
	 * @var array
	 */
	public $bases = array();

	/**
	 * After creating the Hooks on WordPress we lock the usage of the function
	 * @var boolean
	 */
	protected $hook_lock = false;

	/**
	 * Tribe__Events__Rewrite constructor.
	 *
	 * @param WP_Rewrite|null $wp_rewrite
	 */
	public function __construct(WP_Rewrite $wp_rewrite = null) {
		$this->rewrite = $wp_rewrite;
	}

	/**
	 * Static Singleton Factory Method
	 *
	 * @return Tribe__Events__Rewrite
	 */
	public static function instance( $wp_rewrite = null ) {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self( $wp_rewrite );
		}

		return self::$instance;
	}

	/**
	 * Generate the Rewrite Rules
	 *
	 * @param  WP_Rewrite $wp_rewrite WordPress Rewrite that will be modified, pass it by reference (&$wp_rewrite)
	 */
	public function filter_generate( WP_Rewrite $wp_rewrite ) {
		parent::filter_generate( $wp_rewrite );

		/**
		 * Use this to change the Tribe__Events__Rewrite instance before new rules
		 * are committed.
		 *
		 * Should be used when you want to add more rewrite rules without having to
		 * deal with the array merge, noting that rules for The Events Calendar are
		 * themselves added via this hook (default priority).
		 *
		 * @var Tribe__Events__Rewrite $rewrite
		 */
		do_action( 'tribe_events_pre_rewrite', $this );

		/**
		 * Provides an opportunity to modify The Events Calendar's rewrite rules before they
		 * are merged in to WP's own rewrite rules.
		 *
		 * @param array $events_rewrite_rules
		 * @param Tribe__Events__Rewrite $tribe_rewrite
		 */
		$this->rules = apply_filters( 'tribe_events_rewrite_rules_custom', $this->rules, $this );

		$wp_rewrite->rules = $this->rules + $wp_rewrite->rules;
	}

	/**
	 * Sets up the rules required by The Events Calendar.
	 *
	 * This should be called during tribe_events_pre_rewrite, which means other plugins needing to add rules
	 * of their own can do so on the same hook at a lower or higher priority, according to how specific
	 * those rules are.
	 *
	 * @param Tribe__Events__Rewrite $rewrite
	 */
	public function generate_core_rules( Tribe__Events__Rewrite $rewrite ) {
		$rewrite
			// Single
			->single( array( '(\d{4}-\d{2}-\d{2})' ), array( Tribe__Events__Main::POSTTYPE => '%1', 'eventDate' => '%2' ) )
			->single( array( '(\d{4}-\d{2}-\d{2})', 'embed' ), array( Tribe__Events__Main::POSTTYPE => '%1', 'eventDate' => '%2', 'embed' => 1 ) )
			->single( array( '{{ all }}' ), array( Tribe__Events__Main::POSTTYPE => '%1', 'post_type' => Tribe__Events__Main::POSTTYPE, 'eventDisplay' => 'all' ) )

			->single( array( '(\d{4}-\d{2}-\d{2})', 'ical' ), array( Tribe__Events__Main::POSTTYPE => '%1', 'eventDate' => '%2', 'ical' => 1 ) )
			->single( array( 'ical' ), array( 'ical' => 1, 'name' => '%1', 'post_type' => Tribe__Events__Main::POSTTYPE ) )

			// Archive
			->archive( array( '{{ page }}', '(\d+)' ), array( 'eventDisplay' => 'list', 'paged' => '%1' ) )
			->archive( array( '(feed|rdf|rss|rss2|atom)' ), array( 'eventDisplay' => 'list', 'feed' => '%1' ) )
			->archive( array( '{{ month }}' ), array( 'eventDisplay' => 'month' ) )
			->archive( array( '{{ list }}', '{{ page }}', '(\d+)' ), array( 'eventDisplay' => 'list', 'paged' => '%1' ) )
			->archive( array( '{{ list }}' ), array( 'eventDisplay' => 'list' ) )
			->archive( array( '{{ today }}' ), array( 'eventDisplay' => 'day' ) )
			->archive( array( '(\d{4}-\d{2})' ), array( 'eventDisplay' => 'month', 'eventDate' => '%1' ) )
			->archive( array( '(\d{4}-\d{2}-\d{2})' ), array( 'eventDisplay' => 'day', 'eventDate' => '%1' ) )
			->archive( array(), array( 'eventDisplay' => 'default' ) )

			->archive( array( 'ical' ), array( 'ical' => 1 ) )
			->archive( array( '(\d{4}-\d{2}-\d{2})', 'ical' ), array( 'ical' => 1, 'eventDisplay' => 'day', 'eventDate' => '%1' ) )

			// Taxonomy
			->tax( array( '{{ page }}', '(\d+)' ), array( 'eventDisplay' => 'list', 'paged' => '%2' ) )
			->tax( array( '{{ month }}' ), array( 'eventDisplay' => 'month' ) )
			->tax( array( '{{ list }}', '{{ page }}', '(\d+)' ), array( 'eventDisplay' => 'list', 'paged' => '%2' ) )
			->tax( array( '{{ list }}' ), array( 'eventDisplay' => 'list' ) )
			->tax( array( '{{ today }}' ), array( 'eventDisplay' => 'day' ) )
			->tax( array( '{{ day }}', '(\d{4}-\d{2}-\d{2})' ), array( 'eventDisplay' => 'day', 'eventDate' => '%2' ) )
			->tax( array( '(\d{4}-\d{2})' ), array( 'eventDisplay' => 'month', 'eventDate' => '%2' ) )
			->tax( array( '(\d{4}-\d{2}-\d{2})' ), array( 'eventDisplay' => 'day', 'eventDate' => '%2' ) )
			->tax( array( 'feed' ), array( 'eventDisplay' => 'list', 'feed' => 'rss2' ) )
			->tax( array( 'ical' ), array( 'ical' => 1 ) )
			->tax( array( 'feed', '(feed|rdf|rss|rss2|atom)' ), array( 'feed' => '%2' ) )
			->tax( array(), array( 'eventDisplay' => 'default' ) )

			// Tag
			->tag( array( '{{ page }}', '(\d+)' ), array( 'eventDisplay' => 'list', 'paged' => '%2' ) )
			->tag( array( '{{ month }}' ), array( 'eventDisplay' => 'month' ) )
			->tag( array( '{{ list }}', '{{ page }}', '(\d+)' ), array( 'eventDisplay' => 'list', 'paged' => '%2' ) )
			->tag( array( '{{ list }}' ), array( 'eventDisplay' => 'list' ) )
			->tag( array( '{{ today }}' ), array( 'eventDisplay' => 'day' ) )
			->tag( array( '{{ day }}', '(\d{4}-\d{2}-\d{2})' ), array( 'eventDisplay' => 'day', 'eventDate' => '%2' ) )
			->tag( array( '(\d{4}-\d{2})' ), array( 'eventDisplay' => 'month', 'eventDate' => '%2' ) )
			->tag( array( '(\d{4}-\d{2}-\d{2})' ), array( 'eventDisplay' => 'day', 'eventDate' => '%2' ) )
			->tag( array( 'feed' ), array( 'eventDisplay' => 'list', 'feed' => 'rss2' ) )
			->tag( array( 'ical' ), array( 'ical' => 1 ) )
			->tag( array( 'feed', '(feed|rdf|rss|rss2|atom)' ), array( 'feed' => '%2' ) )
			->tag( array(), array( 'eventDisplay' => 'default' ) );
	}

	/**
	 * Filters the post permalink to take 3rd party plugins into account.
	 *
	 * @param  string $permalink Permalink for the post
	 * @param  WP_Post $post Post Object
	 *
	 * @return string      Permalink with the language
	 */
	public function filter_post_type_link( $permalink, $post ) {
		$supported_post_types = array(
			Tribe__Events__Main::POSTTYPE,
			Tribe__Events__Main::VENUE_POST_TYPE,
			Tribe__Events__Main::ORGANIZER_POST_TYPE,
		);

		if ( ! in_array( $post->post_type, $supported_post_types ) ) {
			return $permalink;
		}

		$permalink = str_replace( self::PERCENT_PLACEHOLDER, '%', $permalink );

		/**
		 * Filters a supported post type permalink to allow third-party plugins to add or remove components.
		 *
		 * @param string $permalink The permalink for the post generated by the The Events Calendar.
		 * @param WP_Post $post The current post object.
		 * @param array $supported_post_types An array of post types supported by The Events Calendar.
		 */
		$permalink = apply_filters( 'tribe_events_post_type_permalink', $permalink, $post, $supported_post_types );

		return $permalink;
	}

	/**
	 * Checking if WPML is active on this WP
	 *
	 * @return boolean
	 */
	public function is_wpml_active() {
		return ! empty( $GLOBALS['sitepress'] ) && $GLOBALS['sitepress'] instanceof SitePress;
	}

	/**
	 * Get the base slugs for the Plugin Rewrite rules
	 *
	 * WARNING: Don't mess with the filters below if you don't know what you are doing
	 *
	 * @param  string $method Use "regex" to return a Regular Expression with the possible Base Slugs using l10n
	 * @return object         Return Base Slugs with l10n variations
	 */
	public function get_bases( $method = 'regex' ) {
		$tec = Tribe__Events__Main::instance();

		/**
		 * If you want to modify the base slugs before the i18n happens filter this use this filter
		 * All the bases need to have a key and a value, they might be the same or not.
		 *
		 * Each value is an array of possible slugs: to improve robustness the "original" English
		 * slug is supported in addition to translated forms for month, list, today and day: this
		 * way if the forms are altered (whether through i18n or other custom mods) *after* links
		 * have already been promulgated, there will be less chance of visitors hitting 404s.
		 *
		 * @var array $bases
		 */
		$bases = apply_filters( 'tribe_events_rewrite_base_slugs', array(
			'month' => array( 'month', $tec->monthSlug ),
			'list' => array( 'list', $tec->listSlug ),
			'today' => array( 'today', $tec->todaySlug ),
			'day' => array( 'day', $tec->daySlug ),
			'tag' => array( 'tag', $tec->tag_slug ),
			'tax' => array( 'category', $tec->category_slug ),
			'page' => (array) 'page',
			'all' => (array) 'all',
			'single' => (array) Tribe__Settings_Manager::get_option( 'singleEventSlug', 'event' ),
			'archive' => (array) Tribe__Settings_Manager::get_option( 'eventsSlug', 'events' ),
		) );

		// Remove duplicates (no need to have 'month' twice if no translations are in effect, etc)
		$bases = array_map( 'array_unique', $bases );

		// By default we always have `en_US` to avoid 404 with older URLs
		$languages = apply_filters( 'tribe_events_rewrite_i18n_languages', array_unique( array( 'en_US', get_locale() ) ) );

		// By default we load the Default and our plugin domains
		$domains = apply_filters( 'tribe_events_rewrite_i18n_domains', array(
			'default' => true, // Default doesn't need file path
			'the-events-calendar' => $tec->plugin_dir . 'lang/',
		) );

		/**
		 * Use `tribe_events_rewrite_i18n_slugs_raw` to modify the raw version of the l10n slugs bases.
		 *
		 * This is useful to modify the bases before the method is taken into account.
		 *
		 * @param array  $bases   An array of rewrite bases that have been generated.
		 * @param string $method  The method that's being used to generate the bases; defaults to `regex`.
		 * @param array  $domains An associative array of language domains to use; these would be plugin or themes language
		 *                        domains with a `'plugin-slug' => '/absolute/path/to/lang/dir'`
		 */
		$bases = apply_filters( 'tribe_events_rewrite_i18n_slugs_raw', $bases, $method, $domains );

		if ( 'regex' === $method ) {
			foreach ( $bases as $type => $base ) {
				// Escape all the Bases
				$base = array_map( 'preg_quote', $base );

				// Create the Regular Expression
				$bases[ $type ] = '(?:' . implode( '|', $base ) . ')';
			}
		}

		/**
		 * Use `tribe_events_rewrite_i18n_slugs` to modify the final version of the l10n slugs bases
		 *
		 * At this stage the method has been applied already and this filter will work with the
		 * finalized version of the bases.
		 *
		 * @param array  $bases   An array of rewrite bases that have been generated.
		 * @param string $method  The method that's being used to generate the bases; defaults to `regex`.
		 * @param array  $domains An associative array of language domains to use; these would be plugin or themes language
		 *                        domains with a `'plugin-slug' => '/absolute/path/to/lang/dir'`
		 */
		return (object) apply_filters( 'tribe_events_rewrite_i18n_slugs', $bases, $method, $domains );
	}


	/**
	 * Alias to `$this->add()` but adding the archive base first
	 *
	 * @param array|string $regex The regular expression to catch the URL
	 * @param array  $args  The arguments in which the regular expression "alias" to
	 *
	 * @return Tribe__Events__Rewrite
	 */
	public function archive( $regex, $args = array() ) {
		$default = array(
			'post_type' => Tribe__Events__Main::POSTTYPE,
		);
		$args = array_filter( wp_parse_args( $args, $default ) );

		$regex = array_merge( array( $this->bases->archive ), (array) $regex );

		return $this->add( $regex, $args );
	}

	/**
	 * Alias to `$this->add()` but adding the singular base first
	 *
	 * @param array|string $regex The regular expression to catch the URL
	 * @param array  $args  The arguments in which the regular expression "alias" to
	 *
	 * @return Tribe__Events__Rewrite
	 */
	public function single( $regex, $args = array() ) {
		$regex = array_merge( array( $this->bases->single, '([^/]+)' ), (array) $regex );

		return $this->add( $regex, $args );
	}

	/**
	 * Alias to `$this->add()` but adding the taxonomy base first
	 *
	 * @param array|string $regex The regular expression to catch the URL
	 * @param array  $args  The arguments in which the regular expression "alias" to
	 *
	 * @return Tribe__Events__Rewrite
	 */
	public function tax( $regex, $args = array() ) {
		$default = array(
			'post_type' => Tribe__Events__Main::POSTTYPE,
			Tribe__Events__Main::TAXONOMY => '%1',
		);
		$args = array_filter( wp_parse_args( $args, $default ) );
		$regex = array_merge( array( $this->bases->archive, $this->bases->tax, '(?:[^/]+/)*([^/]+)' ), (array) $regex );

		return $this->add( $regex, $args );
	}

	/**
	 * Alias to `$this->add()` but adding the tag base first
	 *
	 * @param array|string $regex The regular expression to catch the URL
	 * @param array  $args  The arguments in which the regular expression "alias" to
	 *
	 * @return Tribe__Events__Rewrite
	 */
	public function tag( $regex, $args = array() ) {
		$default = array(
			'post_type' => Tribe__Events__Main::POSTTYPE,
			'tag' => '%1',
		);
		$args = array_filter( wp_parse_args( $args, $default ) );
		$regex = array_merge( array( $this->bases->archive, $this->bases->tag, '([^/]+)' ), (array) $regex );

		return $this->add( $regex, $args );
	}


	protected function remove_hooks() {
		parent::remove_hooks();
		remove_filter( 'post_type_link', array( $this, 'filter_post_type_link' ), 15 );
	}

	protected function add_hooks() {
		parent::add_hooks();
		add_action( 'tribe_events_pre_rewrite', array( $this, 'generate_core_rules' ) );
		add_filter( 'post_type_link', array( $this, 'filter_post_type_link' ), 15, 2 );
	}
}
