<?php

namespace Drupal\smmg_member\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use Drupal\contact\Entity\Message;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\smmg_member\Models\Member;
use Drupal\smmg_member\Utility\MemberTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class MemberController extends ControllerBase
{
  use MemberTrait;

  /**
   * @return mixed
   */
  public function landing_page()
  {
    $url_unsubscribe = Url::fromRoute('smmg_member.unsubscribe');
    $url_subscribe = Url::fromRoute('smmg_member.subscribe');

    $variables['url']['subscribe'] = $url_subscribe;
    $variables['url']['unsubscribe'] = $url_unsubscribe;

    $templates = self::getTemplates();
    $template = file_get_contents($templates['landing_page']);

    $build = [
      'description' => [
        '#type' => 'inline_template',
        '#template' => $template,
        '#attached' => ['library' => ['smmg_member/smmg_member.main']],
        '#context' => $variables
      ]
    ];
    return $build;
  }

  /**
   * @param null $email
   * @return array
   */
  public static function subscribeDirect($email = null)
  {
    $email = trim($email);
    $token = Helper::generateToken();

    $valid_email = \Drupal::service('email.validator')->isValid($email);

    if (!empty($email) && $valid_email) {
      // Subscribe direct
      $data['email'] = $email;
      $data['subscribe'] = true;
      $data['token'] = $token;

      $result = self::newSubscriber($data);

      if ($result['status']) {
        $output = self::thankYouPage($result['nid'], $token);
      } else {
        $output['error'] = [
          '#markup' => 'Something went wrong...'
        ];
      }
    } else {
      $output['error'] = [
        '#markup' => 'Invalid email'
      ];
    }
    return $output;
  }

  /**
   * @param $nid
   * @return array
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  static function subscribe($nid)
  {
    return self::updateSubscriber($nid, true);
  }

  /**
   * @param $nid
   * @return array
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  static function unSubscribe($nid)
  {
    return self::updateSubscriber($nid, false);
  }

  /**
   * @param null $nid
   * @param bool $subscribe
   * @return array
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public static function updateSubscriber($nid = null, $subscribe = true): array
  {
    $output = [
      'status' => false,
      'mode' => '',
      'nid' => $nid,
      'message' => '',
      'type' => 'status', // status, warning, error
      'token' => false
    ];

    // valiade number:
    $nid = trim($nid);
    $nid = intval($nid);

    if ($nid !== '') {
      // Load Node
      $entity = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($nid);

      // Node exists ?
      if ($entity && $entity->bundle() == 'member') {
        // Save Subscription
        $entity->get('field_smmg_accept_member')->setValue($subscribe);
        try {
          $entity->save();
        } catch (EntityStorageException $e) {
        }

        // Get Token
        $output['token'] = Helper::getToken($entity);
        $output['status'] = true;

        if ($subscribe) {
          $output['message'] = t('Successfully subscribed to member.');
        } else {
          $output['message'] = t('Successfully unsubscribed from member.');
        }
      }
    } else {
      $output['message'] = t('This user does not exist.');
      $output['level'] = 'error';
    }

    return $output;
  }

  /**
   * @param $data
   * @return array
   */
  public static function newSubscriber($data)
  {
    // Token
    $token = $data['token'];

    $output = [
      'status' => false,
      'mode' => 'save',
      'nid' => false,
      'message' => '',
      'token' => $token
    ];

    // Member
    $subscribe = $data['subscribe'];

    // Fieldset address
    $email = $data['email'];
    $gender = $data['gender'];
    $first_name = $data['first_name'];
    $last_name = $data['last_name'];
    $street_and_number = $data['street_and_number'];
    $zip_code = $data['zip_code'];
    $city = $data['city'];
    $phone = $data['phone'];

    $member_nid = Member::isEmailInUse($email);

    if ($member_nid) {
      $member = self::subscribe($member_nid);

      $output = [
        'status' => true,
        'mode' => 'save',
        'nid' => $member_nid,
        'token' => $member['token'],
        'message' => 'Member Update'
      ];
    } else {
      if ($first_name && $last_name) {
        $title = $first_name . ' ' . $last_name;
      } else {
        $title = $email;
      }

      try {
        // Load List for origin
        $vid = 'smmg_origin';
        $origin_list = Helper::getTermsByName($vid);

        $storage = \Drupal::entityTypeManager()->getStorage('node');
        $new_member = $storage->create([
          'type' => 'member',
          'title' => $title,
          'field_gender' => $gender,
          'field_first_name' => $first_name,
          'field_last_name' => $last_name,
          'field_phone' => $phone,
          'field_street_and_number' => $street_and_number,
          'field_zip_code' => $zip_code,
          'field_city' => $city,
          'field_email' => $email,
          'field_smmg_token' => $token,
          'field_smmg_origin' => $origin_list['member'],

          // Member
          'field_smmg_accept_member' => $subscribe
        ]);

        // Save
        try {
          $new_member->save();
        } catch (EntityStorageException $e) {
        }

        $new_member_nid = $new_member->id();

        // if OK
        if ($new_member_nid) {
          $nid = $new_member_nid;

          $message = t('Information successfully saved.');
          $output['message'] = $message;
          $output['status'] = true;
          $output['nid'] = $nid;
          $output['token'] = $token;

          self::sendNotivicationMail($nid, $token);
        }
      } catch (InvalidPluginDefinitionException $e) {
      } catch (PluginNotFoundException $e) {
      }
    }
    return $output;
  }

  /**
   * @param bool $nid
   * @param bool $token
   * @return array
   */
  public static function byePage($nid = false, $token = false)
  {
    if ($nid != false && !is_numeric($nid)) {
      throw new AccessDeniedHttpException();
    }

    if ($token == false) {
      throw new AccessDeniedHttpException();
    }

    $templates = self::getTemplates();
    $template = file_get_contents($templates['bye_bye']);

    $build = [
      'description' => [
        '#type' => 'inline_template',
        '#template' => $template,
        '#attached' => ['library' => ['smmg_member/smmg_member.main']],
        '#context' => self::memberVariables($nid, $token)
      ]
    ];
    return $build;
  }

  /**
   * @param bool $nid
   * @param bool $token
   * @return array
   */
  public static function thankYouPage($nid = false, $token = false)
  {
    if ($nid != false && !is_numeric($nid)) {
      throw new AccessDeniedHttpException();
    }

    if ($token == false) {
      throw new AccessDeniedHttpException();
    }
    $templates = self::getTemplates();
    $template = file_get_contents($templates['thank_you']);

    $build = [
      'description' => [
        '#type' => 'inline_template',
        '#template' => $template,
        '#attached' => ['library' => ['smmg_member/smmg_member.main']],
        '#context' => self::memberVariables($nid, $token)
      ]
    ];
    return $build;
  }

  /**
   * @param null $nid
   * @param null $token
   * @return array
   */
  public static function memberVariables($nid, $token)
  {
    $variables = [];

    $variables['address']['gender'] = '';
    $variables['address']['first_name'] = '';
    $variables['address']['last_name'] = '';
    $variables['address']['street_and_number'] = '';
    $variables['address']['zip_code'] = '';
    $variables['address']['city'] = '';
    $variables['address']['email'] = '';
    $variables['address']['phone'] = '';

    $variables['member'] = false;

    $variables['id'] = $nid;
    $variables['token'] = $token;

    // Clean Input
    $nid = trim($nid);
    $nid = intval($nid);

    // Load Terms from Taxonomy
    $gender_list = Helper::getTermsByID('smmg_gender');

    // Member & Member
    if ($nid) {
      $member = Node::load($nid);

      if ($member && $member->bundle() == 'member') {
        // Check Token
        $node_token = Helper::getFieldValue($member, 'smmg_token');

        if ($token != $node_token) {
          throw new AccessDeniedHttpException();
        }

        // Address
        $variables['address']['gender'] = Helper::getFieldValue(
          $member,
          'gender',
          $gender_list
        );
        $variables['address']['first_name'] = Helper::getFieldValue(
          $member,
          'first_name'
        );
        $variables['address']['last_name'] = Helper::getFieldValue(
          $member,
          'last_name'
        );
        $variables['address']['street_and_number'] = Helper::getFieldValue(
          $member,
          'street_and_number'
        );
        $variables['address']['zip_code'] = Helper::getFieldValue(
          $member,
          'zip_code'
        );
        $variables['address']['city'] = Helper::getFieldValue($member, 'city');
        $variables['address']['email'] = Helper::getFieldValue(
          $member,
          'email'
        );
        $variables['address']['phone'] = Helper::getFieldValue(
          $member,
          'phone'
        );

        // Member
        $variables['member'] = Helper::getFieldValue(
          $member,
          'smmg_accept_member'
        );
      }
    }
    return $variables;
  }

  public function sandboxEmail(
    $coupon_order_nid,
    $token = null,
    $output_mode = 'html'
  ) {
    $build = false;

    // Get Content
    $data = self::memberVariables($coupon_order_nid, $token);
    $data['sandbox'] = true;

    $templates = self::getTemplates();

    // HTML Email
    if ($output_mode === 'html') {
      // Build HTML Content
      $template = file_get_contents($templates['email_html']);
      $build_html = [
        'description' => [
          '#type' => 'inline_template',
          '#template' => $template,
          '#context' => $data
        ]
      ];

      $build = $build_html;
    }

    // Plaintext
    if ($output_mode === 'plain') {
      // Build Plain Text Content
      $template = file_get_contents($templates['email_plain']);

      $build_plain = [
        'description' => [
          '#type' => 'inline_template',
          '#template' => $template,
          '#context' => $data
        ]
      ];

      $build = $build_plain;
    }

    return $build;
  }

  public function sandboxSendEmail($nid, $token = null, $output_mode = 'html')
  {
    $build = $this->sandboxEmail($nid, $token, $output_mode);

    self::sendNotivicationMail($nid, $token);

    return $build;
  }

  public static function getTemplateNames(): array
  {
    $templates = [
      'landing_page',
      'bye_bye',
      'thank_you',
      'email_html',
      'email_plain'
    ];

    return $templates;
  }

  public static function getTemplates(): array
  {
    $module = 'smmg_member';
    $template_names = self::getTemplateNames();
    $templates = Helper::getTemplates($module, $template_names);

    return $templates;
  }

  public static function APIMember($id): JsonResponse
  {
    $Member = new Member($id);
    $data = $Member->getData();
    return new JsonResponse($data);
  }

  public static function APIMembersSync($changed = 0): JsonResponse
  {
    // Check Input
    if (!$changed) {
      return new JsonResponse('request not valid');
    }

    $members = [];
    // Search all members newer then $changed
    $query = \Drupal::entityTypeManager()->getStorage('node');
    $query_count = $query
      ->getQuery()
      ->condition('type', Member::type)
      ->condition('changed', $changed, '>')
      ->count()
      ->execute();

    // Load only if less ten 200 Nodes
    if ($query_count < 300) {
      $query_result = $query
        ->getQuery()
        ->condition('type', Member::type)
        ->condition('changed', $changed, '>')
        ->sort('nid', 'ASC')
        ->execute();

      // Load Data
      foreach ($query_result as $nid) {
        $member = new Member($nid);
        $members[] = $member->getData();
      }
    }

    // build Response
    $response = [
      'version' => 8,
      'count' => (int) $query_count,
      'members' => $members,
      'nids' => $query_result
    ];

    // return JSON
    return new JsonResponse($response);
  }

  public static function APIMembers(
    $start = 0,
    $length = 0,
    $subscriber_group = null
  ): JsonResponse {
    $Members = [];
    $set = 0;

    // Search all Members
    // Query with entity_type.manager
    $query = \Drupal::entityTypeManager()->getStorage('node');
    $query_count = $query
      ->getQuery()
      ->condition('type', Member::type)
      ->sort('nid', 'ASC')
      ->count()
      ->execute();

    // Count Members
    $number_of = $query_count;

    // if nothing found
    if ($number_of === 0) {
      $response = ['message' => 'no members found', 'count' => 0];
      return new JsonResponse($response);
    }

    // get Nids
    if ($subscriber_group) {
      $query_result = $query
        ->getQuery()
        ->condition('type', Member::type)
        ->sort('nid', 'ASC')
        ->range($start, $length)
        ->condition(Member::field_subscriber_group, $subscriber_group, 'IN')
        ->execute();
    } else {
      $query_result = $query
        ->getQuery()
        ->condition('type', Member::type)
        ->sort('nid', 'ASC')
        ->range($start, $length)
        ->execute();
    }

    if ($query_result) {
      $set = count($query_result);
    }

    // Load Data
    foreach ($query_result as $nid) {
      $Member = new Member($nid);
      $Members[] = $Member->getData();
    }

    // build Response
    $response = [
      'count' => (int) $number_of,
      'set' => (int) $set,
      'start' => (int) $start,
      'subscriber_group' => (int) $subscriber_group,
      'length' => (int) $length,
      'members' => $Members
      //  'nids' => $query_result,
    ];

    // return JSON
    return new JsonResponse($response);
  }

  /**
   * @param Request $request
   * @return JsonResponse
   * @throws EntityStorageException
   * @throws PluginNotFoundException
   * @route smmg_member.api.members.update
   */
  public static function APIMemberUpdate(): JsonResponse
  {
    $post_as_json = \Drupal::request()->getContent();

    $data = json_decode($post_as_json, true);

    $result = Member::updateSubscriber($data);

    return new JsonResponse($result);
  }

  public function APIMembersCount(): JsonResponse
  {
    // Search all Members
    // Query with entity_type.manager
    $query = \Drupal::entityTypeManager()->getStorage('node');
    $query_count = $query
      ->getQuery()
      ->condition('type', Member::type)
      ->count()
      ->execute();

    $response = ['countMembers' => (int) $query_count];
    return new JsonResponse($response);
  }

  /**
   * @param $newsletter_id
   *
   * takes all fake members
   * changes randomly this values in Data with newsletter id:
   *  - unsubscribe
   *  - newsletter open
   * @throws \Exception
   */
  public function testMemberRandomNewsletterChanges($message_id)
  {
    $length = 400;
    $start = range(0, 2000);
    // Search all Members
    // Query with entity_type.manager

    // get Group of message:
    $node_message = Node::load($message_id);
    $groups = Helper::getFieldValue(
      $node_message,
      Member::field_subscriber_group
    );
    $groupNames = Helper::getFieldValue(
      $node_message,
      Member::field_subscriber_group,
      'smmg_subscriber_group',
      full
    );

    $query = \Drupal::entityTypeManager()->getStorage('node');
    $nids = $query
      ->getQuery()
      ->condition('type', Member::type)
      ->condition(Member::field_fake, true)
      ->condition(Member::field_subscriber_group, $groups)
      ->sort('nid', 'ASC')
      ->range($start, $length)
      ->execute();

    shuffle($nids);
    $random_nids = array_splice($nids, 0, 50);

    $nid_with_new_data = [];

    // Load Data
    foreach ($random_nids as $id) {
      $alter = [];
      $alter['id'] = $id;
      $node = Node::load($id);
      if (!empty($node)) {
        $alter['title'] = $node->label();
        $alter['groups'] = $groupNames;
        $telemetry = Helper::getFieldValue($node, Member::field_telemetry);

        if ($telemetry) {
          $old_telemetry = json_decode($telemetry, true);
          $alter['oldData'] = $old_telemetry;
          $new_telemetry = [];
          foreach ($old_telemetry as $message) {
            if (
              $message &&
              isset($message['messageId']) &&
              $message['messageId'] == $message_id
            ) {
              $message['open'] = true;
              $now = time();
              $message['openTS'] = $now;

              // unsubscribe for 1 in 5
              $message['unsubscribe'] = random_int(1, 5) === 1 ? true : false;

              // invalidEmail for 1 in 10
              $message['invalidEmail'] = random_int(1, 10) === 1 ? true : false;
            }
            $new_telemetry[] = $message;
          }
          $alter['newData'] = $new_telemetry;

          $new_telemetry = \json_encode($new_telemetry, true);
          $node->set(Member::field_telemetry, $new_telemetry);
          $node->save();
          $nid_with_new_data[] = $alter;
        }
      }
    }

    return new JsonResponse($nid_with_new_data);
  }



  public static function countMemberInVocabularyField($field, $tid)
  {
    $query = \Drupal::entityTypeManager()->getStorage('node');
    $query_count = $query
      ->getQuery()
      ->condition('type', Member::type)
      ->condition($field, $tid)
      ->count()
      ->execute();

    return $query_count;
  }


  function APITermsSubscriberGroup()
  {
    $name = 'subscriber-group';
    $vocabulary = Member::term_subscriber_group;
    $field= Member::field_subscriber_group;
    $terms = [];

    $response = $this->APITerms($vocabulary, $field, $terms, $name);

    return new JsonResponse($response);
  }

  function APITermsGender()
  {
    $name = 'gender';
    $vocabulary = Member::term_gender;
    $field= Member::field_gender;
    $terms = [];

    $response = $this->APITerms($vocabulary, $field, $terms, $name);

    return new JsonResponse($response);
  }

  function APITermsOrigin()
  {
    $name = 'origin';
    $vocabulary = Member::term_origin;
    $field= Member::field_origin;
    $terms = [];

    $response = $this->APITerms($vocabulary, $field, $terms, $name);

    return new JsonResponse($response);
  }

  function APITermsCountry()
  {
    $name = 'country';
    $vocabulary = Member::term_country;
    $field= Member::field_country;
    $terms = [];

    $response = $this->APITerms($vocabulary, $field, $terms, $name);

    return new JsonResponse($response);
  }



  /**
   * @param string $vocabulary
   * @param string $field
   * @param array $terms
   * @param string $name
   * @return array
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function APITerms(string $vocabulary, string $field, array $terms, string $name): array
  {
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree($vocabulary);
    foreach ($nodes as $node) {
      $terms[] = array(
        'id' => (int)$node->tid,
        'name' => $node->name,
        'count' => (int)self::countMemberInVocabularyField($field, $node->tid),
      );
    }
    /* DEV: Insert Mollo Token from /site/files/settings.php */
    $mollo = Settings::get('mollo');

    $response = [
      'name' => 'api/terms/'.$name,
      'version' => '1.0.0',
      'token' => $mollo['token'],
      'terms' => $terms];
    return $response;
  }


}
