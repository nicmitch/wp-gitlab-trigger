<?php
/**
 *  @package WP Gitlab Trigger
 *
 * Plugin Name: WP Gitlab Trigger
 * Plugin URI:
 * Description: A plugin which trigger a gitlab hook
 * Version: 1.0
 * Author: Nicola Merici
 * Author URI: https://www.nicolamerici.com
 * License: GPLv3 or later
 * Text Domain: wp-gitlab-trigger
 */

 defined( 'ABSPATH' ) or die('You do not have access to this file, sorry mate');

 class WpGitlabTrigger {

     /**
     * Constructor
     *
     * @since 1.0.0
     **/
     public function __construct() {

      // Hook into the admin menu
      add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) );

      // Add Settings and Fields
      add_action( 'admin_init', array( $this, 'setup_sections' ) );
      add_action( 'admin_init', array( $this, 'setup_developer_fields' ) );
      add_action( 'admin_footer', array( $this, 'run_the_mighty_javascript' ) );
      add_action( 'admin_bar_menu', array( $this, 'add_to_admin_bar' ), 90 );
     }

     /**
     * Main Plugin Page markup
     *
     * @since 1.0.0
     **/
     public function plugin_settings_page_content() {?>
     	<div class="wrap">
     		<h2><?php _e('Public Website', 'wp-gitlab-trigger-deploy');?></h2>
         <hr>
         <?php /*<h3><?php _e('Public Website', 'wp-gitlab-trigger-deploy');?></h3> */ ?>
         <p style="font-size: 12px"><em><?php _e('Do not abuse the Build Site button', 'wp-gitlab-trigger-deploy');?></em></p>
         <button id="build_button" class="button button-primary" name="submit" type="submit">
           <?php _e('Public Site', 'wp-gitlab-trigger-deploy');?>
         </button>
         <br><br>
         <hr>

         <?php /*
         <h3><?php _e('Deploy Status', 'wp-gitlab-trigger-deploy');?></h3>
         <button id="status_button" class="button button-primary" name="submit" type="submit" style="margin: 0 0 16px;">
           <?php _e('Get Deploys Status', 'wp-gitlab-trigger-deploy');?>
         </button>
         */ ?>

         <div style="margin: 0 0 16px;">
             <a id="build_img_link" href="">
                 <img id="build_img" src=""/>
             </a>
         </div>
         <div>
             <!-- <p id="deploy_status"></p> -->
             <p id="deploy_id"></p>
             <div style="display: flex;"><p id="deploy_finish_time"></p><p id="deploy_loading"></p></div>
             <p id="deploy_ssl_url"></p>
         </div>

         <div id="deploy_preview"></div>
         <?php /*
         <hr>
         <h3><?php _e('Previous Builds', 'wp-gitlab-trigger-deploy');?></h3>
         <button id="previous_deploys" class="button button-primary" name="submit" type="submit" style="margin: 0 0 16px;">
           <?php _e('Get All Previous Deploys', 'wp-gitlab-trigger-deploy');?>
         </button>
         <ul id="previous_deploys_container" style="list-style: none;"></ul>

         */ ?>
     	</div> <?php
     }

     /**
     * Developer Settings (subpage) markup
     *
     * @since 1.0.0
     **/
     public function plugin_settings_developer_content() {?>
     	<div class="wrap">
     		<h1><?php _e('Developer Settings', 'wp-gitlab-trigger-deploy');?></h1>
     		<p><?php _e('Do not change this if you dont know what you are doing.', 'wp-gitlab-trigger-deploy');?></p>
             <hr>

             <?php
             if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ){
                   $this->admin_notice();
             } ?>
     		<form method="POST" action="options.php">
           <?php
               settings_fields( 'developer_webhook_fields' );
               do_settings_sections( 'developer_webhook_fields' );
               submit_button();
           ?>
     		</form>

     	</div> <?php
     }

     /**
     * The Mighty JavaScript
     *
     * @since 1.0.0
     **/
     public function run_the_mighty_javascript() {
         // TODO: split up javascript to allow to be dynamically imported as needed
         // $screen = get_current_screen();
         // if ( $screen && $screen->parent_base != 'developer_webhook_fields' && $screen->parent_base != 'deploy_webhook_fields_sub' ) {
         //     return;
         // }
         ?>
         <script type="text/javascript" >
         console.log('run_the_mighty_javascript');
         jQuery(document).ready(function($) {
             var _this = this;
             $( ".webhook-deploy_page_developer_webhook_fields td > input" ).css( "width", "100%");

             var webhook_url = '<?php echo(get_option('webhook_address')) ?>';
             var netlify_user_agent = '<?php echo(get_option('netlify_user_agent')) ?>';
             var netlify_api_key = '<?php echo(get_option('netlify_api_key'))?>'
             var netlify_site_id = '<?php echo(get_option('netlify_site_id')) ?>';

             var netlifySites = "https://api.netlify.com/api/v1/sites/";
             var req_url = netlifySites + netlify_site_id + '/deploys?access_token=' + netlify_api_key;

             function getDeployData() {
                 $.ajax({
                     type: "GET",
                     url: req_url
                 }).done(function(data) {
                     appendStatusData(data[0]);
                 })
                 .fail(function() {
                     console.error("error res => ", this)
                 })
             }

             function getAllPreviousBuilds() {
                 $.ajax({
                     type: "GET",
                     url: req_url
                 }).done(function(data) {
                     var buildNo = 1;
                     data.forEach(function(item) {
                         var deploy_preview_url = '';
                         if (data.deploy_ssl_url) {
                             deploy_preview_url = data.deploy_ssl_url
                         } else {
                             deploy_preview_url = data.deploy_url
                         }
                         $('#previous_deploys_container').append(
                             '<li style="margin: 0 auto 16px"><hr><h3>No: ' + buildNo + ' - ' + item.name + '</h3><h4>Created at: ' +  new Date(item.created_at.toString()).toLocaleString() + '</h4><h4>' + item.title + '</h4><p>Id: ' + item.id + '</p><p>Deploy Time: ' + item.deploy_time + '</p><p>Branch: ' + item.branch + '</p><a href="' + item.deploy_preview_url + '">Preview Build</a></li>'
                         );
                         buildNo++;
                     })
                 })
                 .fail(function() {
                     console.error("error res => ", this)
                 })
             }

             function runSecondFunc() {
                 $.ajax({
                     type: "GET",
                     url: req_url
                 }).done(function(data) {
                     $( "#build_img_link" ).attr("href", `${data.admin_url}`);
                     // $( "#build_img" ).attr("src", `https://api.netlify.com/api/v1/badges/${ netlify_site_id }/deploy-status`);
                 })
                 .fail(function() {
                     console.error("error res => ", this)
                 })

                 // $( "#build_status" ).html('Deploy building');
             }

             function appendStatusData(data) {
                 var d = new Date();
                 var p = d.toLocaleString();
                 var yo = new Date(data.created_at);
                 var created = yo.toLocaleString();
                 var current_state = data.state;

                 if (data.state === 'ready') {
                     current_state = "Success"
                 }

                 /*
                 $gitlab_branch = get_option('option_branch');
                  $gitlab_username = get_option('option_username');
                  $gitlab_password = get_option('option_password');
                  // if environment variables are set, then trigger static build
                  if ($gitlab_branch && $gitlab_username && $gitlab_password) {
                    wp_remote_post('https://gitlab.com/api/v4/projects/'.$gitlab_username.'/ref/'.$gitlab_branch.'/trigger/pipeline?token='.$gitlab_password);
                  }
                  */

                 if (data.state !== 'ready') {
                     $( "#deploy_finish_time" ).html( "Building Site" );
                     $( "#build_img" ).attr("src", `https://api.netlify.com/api/v1/badges/${ netlify_site_id }/deploy-status`);
                     var dots = window.setInterval( function() {
                         var wait = document.getElementById('deploy_loading');
                         if ( wait.innerHTML.length >= 3 ) {
                             wait.innerHTML = "";
                         }
                         else {
                             wait.innerHTML += ".";
                         }
                     },
                     500);
                 } else {
                     var deploy_preview_url = '';

                     if (data.deploy_ssl_url) {
                         deploy_preview_url = data.deploy_ssl_url
                     } else {
                         deploy_preview_url = data.deploy_url
                     }

                     $( "#deploy_id" ).html( "ID: " + data.id + "" );
                     $( "#deploy_finish_time" ).html( "Build Completed: " + created );
                     $( "#build_img" ).attr("src", `https://api.netlify.com/api/v1/badges/${ netlify_site_id }/deploy-status`);
                     $( "#deploy_ssl_url" ).html( "Deploy URL: <a href='" + deploy_preview_url + "'>" + data.deploy_ssl_url + "</a>");
                     $( "#deploy_preview" ).html( `<iframe style="width: 100%; min-height: 540px" id="frameLeft" src="${deploy_preview_url}"/>`)
                 }


             }

             function gitlabTrigger() {
                 return $.ajax({
                     type: "POST",
                     url: webhook_url,
                     dataType: "json",
                     header: {
                         "User-Agent": netlify_user_agent
                     }
                 });
             }

             /*
             $("#status_button").on("click", function(e) {
                 e.preventDefault();
                 getDeployData();
             });

             $("#previous_deploys").on("click", function(e) {
                 e.preventDefault();
                 getAllPreviousBuilds();
             });
             */

             $("#build_button").on("click", function(e) {

                 // hide deploy
                 $('#build_img_link').attr('href', '');
                 $('#build_img').attr('src', '');
                 $('#deploy_id').html('');
                 $('#deploy_finish_time').html('');
                 $('#deploy_ssl_url').html('');
                 $('#deploy_preview').html('');

                 e.preventDefault();

                 gitlabTrigger().done(function() {
                     console.log("success")
                     getDeployData();
                     $( "#build_status" ).html('Deploy building');
                 })
                 .fail(function() {
                     console.error("error res => ", this)
                     $( "#build_status" ).html('There seems to be an error with the build', this);
                 })
             });

             $(document).on('click', '#wp-admin-bar-netlify-deploy-button', function(e) {
                 e.preventDefault();

                 var $button = $(this),
                     $buttonContent = $button.find('.ab-item:first');

                 if ($button.hasClass('deploying') || $button.hasClass('running')) {
                     return false;
                 }

                 $button.addClass('running').css('opacity', '0.5');

                 gitlabTrigger().done(function() {
                     var $badge = $('#admin-bar-netlify-deploy-status-badge');

                     $button.removeClass('running');
                     $button.addClass('deploying');

                     $buttonContent.find('.ab-label').text('Deploying…');

                     if ($badge.length) {
                         if (!$badge.data('original-src')) {
                             $badge.data('original-src', $badge.attr('src'));
                         }

                         $badge.attr('src', $badge.data('original-src') + '?updated=' + Date.now());
                     }
                 })
                 .fail(function() {
                     $button.removeClass('running').css('opacity', '1');
                     $buttonContent.find('.dashicons-hammer')
                         .removeClass('dashicons-hammer').addClass('dashicons-warning');

                     console.error("error res => ", this)
                 })
             });
         });
         </script> <?php
     }

     /**
     * Plugin Menu Items Setup
     *
     * @since 1.0.0
     **/
     public function create_plugin_settings_page() {
         $run_deploys = apply_filters( 'netlify_deploy_capability', 'manage_options' );
         $adjust_settings = apply_filters( 'netlify_adjust_settings_capability', 'manage_options' );

         if ( current_user_can( $run_deploys ) ) {
             $page_title = __('Public Website', 'wp-gitlab-trigger-deploy');
             $menu_title = __('Public Website', 'wp-gitlab-trigger-deploy');
             $capability = $run_deploys;
             $slug = 'deploy_webhook_fields';
             $callback = array( $this, 'plugin_settings_page_content' );
             $icon = 'data:image/svg+xml;base64,PHN2ZyBpZD0iQ2FwYV8xIiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCA1MTIgNTEyIiBoZWlnaHQ9IjUxMiIgdmlld0JveD0iMCAwIDUxMiA1MTIiIHdpZHRoPSI1MTIiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CiAgPHBhdGggZmlsbD0iI0ZGRiIgZD0ibTUxMS40IDM4LjIyMmMtMS4xMDktMjAuMzM4LTE3LjI4NC0zNi41MTEtMzcuNjIyLTM3LjYyMS00MS4wMzgtMi4yNDItMTIxLjM0Mi0uMDYxLTE5OC4xMyAzOS42NTYtMzkuMTQ1IDIwLjI0OC04MC41NDUgNTQuNTc3LTExMy41ODQgOTQuMTg1LS40MDcuNDg4LS44MDMuOTc5LTEuMjA3IDEuNDY4bC03NC45OCA1Ljc5MmMtMTIuMzQyLjk1NC0yMy4zMzUgNy40MjMtMzAuMTYxIDE3Ljc0N2wtNTEuMTU0IDc3LjM3MmMtNS4xNzcgNy44My02IDE3LjYyOS0yLjIwMyAyNi4yMTIgMy43OTggOC41ODQgMTEuNjAyIDE0LjU2NiAyMC44NzcgMTYuMDAzbDYzLjE3MSA5Ljc4NGMtLjIyMyAxLjIyOC0uNDQ3IDIuNDU1LS42NTIgMy42ODMtMi4xMDMgMTIuNTggMi4wNjUgMjUuNTE0IDExLjE1MSAzNC41OTlsODcuOTkyIDg3Ljk5M2M3LjUzMyA3LjUzMyAxNy43MTIgMTEuNjg2IDI4LjE0MiAxMS42ODYgMi4xNDggMCA0LjMwOC0uMTc3IDYuNDU4LS41MzYgMS4yMjgtLjIwNSAyLjQ1NS0uNDI5IDMuNjgzLS42NTJsOS43ODQgNjMuMTcyYzEuNDM3IDkuMjc1IDcuNDE5IDE3LjA4IDE2LjAwMSAyMC44NzcgMy41NzEgMS41OCA3LjM1IDIuMzYgMTEuMTEyIDIuMzYgNS4yODMtLjAwMSAxMC41MjktMS41MzkgMTUuMTAxLTQuNTYybDc3LjM3Mi01MS4xNTVjMTAuMzI1LTYuODI3IDE2Ljc5My0xNy44MiAxNy43NDUtMzAuMTYxbDUuNzkyLTc0Ljk3OWMuNDg5LS40MDQuOTgxLS44IDEuNDY5LTEuMjA3IDM5LjYwOS0zMy4wMzkgNzMuOTM5LTc0LjQzOSA5NC4xODYtMTEzLjU4NSAzOS43MTktNzYuNzkxIDQxLjg5Ni0xNTcuMDk2IDM5LjY1Ny0xOTguMTMxem0tMTc1LjM5NCAzOTMuMDM3LTc0LjAxMSA0OC45MzMtOS41MzYtNjEuNTY1YzMxLjI4LTkuMTk3IDYyLjIyMy0yMy45MjcgOTEuNzAyLTQzLjY2bC0zLjc3MyA0OC44NDVjLS4yMzUgMy4wNDctMS44MzMgNS43NjItNC4zODIgNy40NDd6bS0xMjkuODk1LTM3LjM3Ny04Ny45OTMtODcuOTkzYy0yLjI0NS0yLjI0Ni0zLjI4My01LjQwMS0yLjc3NC04LjQ0IDIuNjE2LTE1LjY0MyA2LjY4MS0zMC41MzQgMTEuNzEzLTQ0LjU2MmwxMzIuMDI4IDEzMi4wMjhjLTE2Ljg0OCA2LjAzNS0zMS45MzkgOS42MzUtNDQuNTM0IDExLjc0MS0zLjA0NC41MDYtNi4xOTUtLjUyOS04LjQ0LTIuNzc0em0tMTE3LjkyMy0yMjIuMjY5IDQ4Ljg0NC0zLjc3M2MtMTkuNzM0IDI5LjQ3OS0zNC40NjQgNjAuNDIyLTQzLjY2MSA5MS43MDJsLTYxLjU2NC05LjUzNSA0OC45MzQtNzQuMDEyYzEuNjg2LTIuNTUgNC40MDEtNC4xNDcgNy40NDctNC4zODJ6bTI3MC4xNTUgMTU1LjI4NmMtMjQuMjMzIDIwLjIxMy00Ny43NTYgMzQuODMzLTY5LjQzOCA0NS40MTJsLTE0OS4yMjEtMTQ5LjIyMWMxMy44NTgtMjguMzA0IDMwLjc3MS01MS44NzMgNDUuNDE3LTY5LjQzMSAzMC41NzUtMzYuNjU1IDY4LjYwMi02OC4yNzYgMTA0LjMzMS04Ni43NTYgNzAuNDc0LTM2LjQ1MyAxNDQuNzI1LTM4LjQxNiAxODIuNzEzLTM2LjM0OCA1LjAyOC4yNzQgOS4wMjcgNC4yNzMgOS4zMDEgOS4zMDIgMi4wNzEgMzcuOTg4LjEwNCAxMTIuMjM4LTM2LjM0OSAxODIuNzEzLTE4LjQ3OSAzNS43MjgtNTAuMSA3My43NTQtODYuNzU0IDEwNC4zMjl6Ii8+CiAgPHBhdGggZmlsbD0iI0ZGRiIgZD0ibTM1MC43MjEgMjM2LjI0M2MxOS4yMDItLjAwMiAzOC40MTItNy4zMTIgNTMuMDMxLTIxLjkzMSAxNC4xNjYtMTQuMTY1IDIxLjk2Ni0zMi45OTkgMjEuOTY2LTUzLjAzMXMtNy44MDEtMzguODY2LTIxLjk2Ni01My4wMzFjLTI5LjI0Mi0yOS4yNDMtNzYuODIyLTI5LjI0MS0xMDYuMDYyIDAtMTQuMTY2IDE0LjE2NS0yMS45NjcgMzIuOTk5LTIxLjk2NyA1My4wMzFzNy44MDIgMzguODY2IDIxLjk2NyA1My4wMzFjMTQuNjIyIDE0LjYyMiAzMy44MjIgMjEuOTMzIDUzLjAzMSAyMS45MzF6bS0zMS44Mi0xMDYuNzgxYzguNzcyLTguNzczIDIwLjI5NS0xMy4xNTkgMzEuODE4LTEzLjE1OSAxMS41MjQgMCAyMy4wNDcgNC4zODYgMzEuODE5IDEzLjE1OSA4LjQ5OSA4LjQ5OSAxMy4xNzkgMTkuNzk5IDEzLjE3OSAzMS44MThzLTQuNjggMjMuMzItMTMuMTc5IDMxLjgxOWMtMTcuNTQ0IDE3LjU0NS00Ni4wOTMgMTcuNTQ0LTYzLjYzOCAwLTguNDk5LTguNDk5LTEzLjE4LTE5Ljc5OS0xMy4xOC0zMS44MThzNC42ODItMjMuMzIgMTMuMTgxLTMxLjgxOXoiLz4KICA8cGF0aCBmaWxsPSIjRkZGIiBkPSJtMTUuMzAxIDQyMS45MzhjMy44MzkgMCA3LjY3OC0xLjQ2NCAxMC42MDYtNC4zOTRsNDguOTczLTQ4Ljk3M2M1Ljg1OC01Ljg1OCA1Ljg1OC0xNS4zNTUgMC0yMS4yMTMtNS44NTctNS44NTgtMTUuMzU1LTUuODU4LTIxLjIxMyAwbC00OC45NzIgNDguOTczYy01Ljg1OCA1Ljg1OC01Ljg1OCAxNS4zNTUgMCAyMS4yMTMgMi45MjggMi45MjkgNi43NjcgNC4zOTQgMTAuNjA2IDQuMzk0eiIvPgogIDxwYXRoIGZpbGw9IiNGRkYiIGQ9Im0xMTkuNzYxIDM5Mi4yMzljLTUuODU3LTUuODU4LTE1LjM1NS01Ljg1OC0yMS4yMTMgMGwtOTQuMTU0IDk0LjE1NWMtNS44NTggNS44NTgtNS44NTggMTUuMzU1IDAgMjEuMjEzIDIuOTI5IDIuOTI5IDYuNzY3IDQuMzkzIDEwLjYwNiA0LjM5M3M3LjY3OC0xLjQ2NCAxMC42MDYtNC4zOTRsOTQuMTU0LTk0LjE1NGM1Ljg1OS01Ljg1OCA1Ljg1OS0xNS4zNTUuMDAxLTIxLjIxM3oiLz4KICA8cGF0aCBmaWxsPSIjRkZGIiBkPSJtMTQzLjQyOSA0MzcuMTItNDguOTczIDQ4Ljk3M2MtNS44NTggNS44NTgtNS44NTggMTUuMzU1IDAgMjEuMjEzIDIuOTI5IDIuOTI5IDYuNzY4IDQuMzk0IDEwLjYwNiA0LjM5NHM3LjY3OC0xLjQ2NCAxMC42MDYtNC4zOTRsNDguOTczLTQ4Ljk3M2M1Ljg1OC01Ljg1OCA1Ljg1OC0xNS4zNTUgMC0yMS4yMTMtNS44NTctNS44NTgtMTUuMzU1LTUuODU4LTIxLjIxMiAweiIvPjwvc3ZnPgo=';
             $position = 100;

             add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
         }

         if ( current_user_can( $adjust_settings ) ) {
             $sub_page_title = __('Developer Settings', 'wp-gitlab-trigger-deploy');
             $sub_menu_title = __('Developer Settings', 'wp-gitlab-trigger-deploy');
             $sub_capability = $adjust_settings;
             $sub_slug = 'developer_webhook_fields';
             $sub_callback = array( $this, 'plugin_settings_developer_content' );

             add_submenu_page( $slug, $sub_page_title, $sub_menu_title, $sub_capability, $sub_slug, $sub_callback );
         }


     }

     /**
     * Notify Admin on Successful Update
     *
     * @since 1.0.0
     **/
     public function admin_notice() { ?>
         <div class="notice notice-success is-dismissible">
             <p><?php _e('Your settings have been updated!', 'wp-gitlab-trigger-deploy');?></p>
         </div>
     <?php
     }

     /**
     * Setup Sections
     *
     * @since 1.0.0
     **/
     public function setup_sections() {
         add_settings_section( 'developer_section', __('Webhook Settings', 'wp-gitlab-trigger-deploy'), array( $this, 'section_callback' ), 'developer_webhook_fields' );
     }

     /**
     * Check it wont break on build and deploy
     *
     * @since 1.0.0
     **/
     public function section_callback( $arguments ) {
     	switch( $arguments['id'] ){
     		case 'developer_section':
     			echo __('The build and deploy status will not work without these fields entered corrently', 'wp-gitlab-trigger-deploy');
     			break;
     	}
     }

     /**
     * Fields used for developer input data
     *
     * @since 1.0.0
     **/
     public function setup_developer_fields() {
         $fields = array(
           array(
             'uid' => 'webhook_address',
             'label' => __('Webhook Build URL', 'wp-gitlab-trigger-deploy'),
             'section' => 'developer_section',
             'type' => 'text',
                 'placeholder' => 'https://',
                 'default' => '',
             ),
             array(
             'uid' => 'netlify_site_id',
             'label' => __('Netlify site_id', 'wp-gitlab-trigger-deploy'),
             'section' => 'developer_section',
             'type' => 'text',
                 'placeholder' => 'e.g. 5b8e927e-82e1-4786-4770-a9a8321yes43',
                 'default' => '',
             ),
             array(
             'uid' => 'netlify_api_key',
             'label' => __('Netlify API Key', 'wp-gitlab-trigger-deploy'),
             'section' => 'developer_section',
             'type' => 'text',
                 'placeholder' => __('GET O-AUTH TOKEN', 'wp-gitlab-trigger-deploy'),
                 'default' => '',
           ),
             array(
             'uid' => 'netlify_user_agent',
             'label' => __('User-Agent Site Value', 'wp-gitlab-trigger-deploy'),
             'section' => 'developer_section',
             'type' => 'text',
                 'placeholder' => 'Website Name (and-website-url.netlify.com)',
                 'default' => '',
           )
         );
       foreach( $fields as $field ){
           add_settings_field( $field['uid'], $field['label'], array( $this, 'field_callback' ), 'developer_webhook_fields', $field['section'], $field );
             register_setting( 'developer_webhook_fields', $field['uid'] );
       }
     }

     /**
     * Field callback for handling multiple field types
     *
     * @since 1.0.0
     * @param $arguments
     **/
     public function field_callback( $arguments ) {

         $value = get_option( $arguments['uid'] );

         if ( !$value ) {
             $value = $arguments['default'];
         }

         switch( $arguments['type'] ){
             case 'text':
             case 'password':
             case 'number':
                 printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
                 break;
             case 'time':
               printf( '<input name="%1$s" id="%1$s" type="time" value="%2$s" />', $arguments['uid'], $value );
               break;
             case 'textarea':
                 printf( '<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $arguments['uid'], $arguments['placeholder'], $value );
                 break;
             case 'select':
             case 'multiselect':
                 if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
                     $attributes = '';
                     $options_markup = '';
                     foreach( $arguments['options'] as $key => $label ){
                         $options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $value[ array_search( $key, $value, true ) ], $key, false ), $label );
                     }
                     if( $arguments['type'] === 'multiselect' ){
                         $attributes = ' multiple="multiple" ';
                     }
                     printf( '<select name="%1$s[]" id="%1$s" %2$s>%3$s</select>', $arguments['uid'], $attributes, $options_markup );
                 }
                 break;
             case 'radio':
             case 'checkbox':
                 if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
                     $options_markup = '';
                     $iterator = 0;
                     foreach( $arguments['options'] as $key => $label ){
                         $iterator++;
                         $options_markup .= sprintf( '<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>', $arguments['uid'], $arguments['type'], $key, checked( count($value) > 0 ? $value[ array_search( $key, $value, true ) ] : false, $key, false ), $label, $iterator );
                     }
                     printf( '<fieldset>%s</fieldset>', $options_markup );
                 }
                 break;
         }
     }

     /**
     * Add Deploy Button and Deployment Status to admin bar
     *
     * @since 1.1.0
     **/
     public function add_to_admin_bar( $admin_bar ) {

         $see_deploy_status = apply_filters( 'netlify_status_capability', 'manage_options' );
         $run_deploys = apply_filters( 'netlify_deploy_capability', 'manage_options' );

         if ( current_user_can( $run_deploys ) ) {
             $webhook_address = get_option( 'webhook_address' );

             if ( $webhook_address ) {
                 $button = array(
                     'id' => 'netlify-deploy-button',
                     'title' => '<div style="cursor: pointer;"><span class="ab-icon dashicons dashicons-hammer"></span> <span class="ab-label">'. __('Deploy Site', 'wp-gitlab-trigger-deploy') .'</span></div>'
                 );

                 $admin_bar->add_node( $button );
             }
         }

         if ( current_user_can( $see_deploy_status ) ) {
             $netlify_site_id = get_option( 'netlify_site_id' );

             if ( $netlify_site_id ) {
                 $badge = array(
                     'id' => 'netlify-deploy-status-badge',
                     'title' => sprintf( '<div style="display: flex; height: 100%%; align-items: center;">
                             <img id="admin-bar-netlify-deploy-status-badge" src="https://api.netlify.com/api/v1/badges/%s/deploy-status" alt="'. __('Netlify deply status', 'wp-gitlab-trigger-deploy') .'" style="width: auto; height: 16px;" />
                         </div>', $netlify_site_id )
                 );

                 $admin_bar->add_node( $badge );
             }
         }

     }

 }

 new WpGitlabTrigger;
