<?php
function _watersportkampen_bootstrap_get_sports(){
  $sports = array(
    'Golfsurfen' => 'Golfsurfen',
    'Windsurfen' => 'Windsurfen',
    'Kitesurfen' =>'Kitesurfen',
    'Rafting'=>'Rafting',
    'Zeilen'=>'Zeilen' );
  return $sports;
}






function watersportkampen_bootstrap_form_culturefeed_entry_ui_event_form_alter(&$form, &$form_state, $event= NULL){
  $sports = _watersportkampen_bootstrap_get_sports();

  if(isset($form['#event'])){
    $keywords = $form['#event']->getKeywords();

    $sports_default_value = array_intersect(array_map('strtolower', $keywords), array_map('strtolower', $sports));
    $sports_default_value = array_map('ucfirst', $sports_default_value);

    foreach ($sports_default_value as $sport_default_value) {
      try{
        $form['#event']->deleteKeyword(strtolower($sport_default_value));
      }
      catch (Exception $e){

      }

      try{
        $form['#event']->deleteKeyword(ucfirst($sport_default_value));
      }
      catch (Exception $e){

      }
    }
  }


  $form['basic']['title']['#weight'] = 1;

  $form['basic']['sports'] = array(
      '#type' => 'checkboxes',
      '#title' => 'Sporten',
      '#options'=>$sports,
      '#description'=>'Kies hier de sporten die voorkomen in uw sportkamp',
      '#weight'=>1,
      '#default_value' => isset($sports_default_value) ? $sports_default_value : array()
    );



  $form['basic']['location']['#weight'] = 2;
  $form['basic']['when']['#weight'] = 3;
  unset($form['basic']['what']);

  unset($form['extra']['tags']);
  unset($form['extra']['old_tags']);

  if (isset($form['#event'])) {
    watersportkampen_bootstrap_form_culturefeed_entry_ui_tags_form_alter($form, $form_state, $form['#event']);
  }
  else {
    watersportkampen_bootstrap_form_culturefeed_entry_ui_tags_form_alter($form, $form_state);
  }

  $form['extra']['description']['#weight'] = 6;
  $form['extra']['tags']['#weight'] = 7;
  $form['extra']['old_tags']['#weight'] = 8;
  $form['extra']['links']['#weight'] = 9;
  $form['extra']['add_more_links']['#weight'] = 10;
  $form['extra']['add_more_links']['#limit_validation_errors'] = array();
  $form['extra']['photo']['#weight'] = 11;
  $form['extra']['price']['#weight'] = 12;
  $form['extra']['organiser']['#weight'] = 13;
  $form['extra']['age_category']['#weight'] = 14;
  $form['extra']['age']['#weight'] = 15;
  $form['extra']['fly']['#weight'] = 16;
  $form['extra']['vertical_tabs']['#weight'] = 17;
  dpm($form);

  unset($form['extra']['performers']);
  unset($form['extra']['language']);
  unset($form['extra']['entrance']);
  unset($form['extra']['translations']);
  unset($form['extra']['publication_date']);

  unset($form['submit']);

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#validate' => array('watersportkampen_bootstrap_event_save_validate'),
  );

}



 /**
 * Validate the event form.
 */
function watersportkampen_bootstrap_event_save_validate($form, &$form_state){
  $values = $form_state['values'];

  // Validate age.
  if ($values['age'] && !is_numeric($values['age'])) {
    form_set_error('age', t('Please fill in correct age'));
  }

  // Validate period.
  if ($values['when']['date_control']['type'] == 'period') {

    $period = $values['when']['date_control']['period'];

    // Form validation hasn't processed the dates values yet, so don't validate.
    if (!is_array($period['start_date']) && !is_array($period['end_date'])) {

      // The dates.
      try {
        CultureFeed_Cdb_Data_Calendar::validateDate($period['start_date']);
        CultureFeed_Cdb_Data_Calendar::validateDate($period['end_date']);
      }
      catch (UnexpectedValueException $e) {
        form_set_error('when][date_control][period', t('Please fill in correct date and time'));
      }

      // Order of dates.
      $start_date = strtotime($period['start_date']);
      $end_date = strtotime($period['end_date']);
      if ($start_date > $end_date) {
        form_set_error('when][date_control][period', t('Date field is invalid. The end date is earlier than the beginning date.'));
      }

    }

  }

  // Validate timestamps.
  if ($values['when']['date_control']['type'] == 'timestamps') {

    $timestamps = $values['when']['date_control']['timestamps']['stamps'];

    foreach ($timestamps as $key => $timestamp) {

      // Form validation hasn't processed the dates values yet, so don't validate.
      if (is_array($timestamp['date'])) {
        continue;
      }

      // The date.
      try {
        CultureFeed_Cdb_Data_Calendar::validateDate($timestamp['date']);
      } catch (UnexpectedValueException $e) {
        form_set_error('when][date_control][timestamps][stamps][' . $key, t('Please fill in correct date and time'));
      }

      // The times.
      $start_time = isset($timestamp['start_time']) ? strtotime($timestamp['start_time']) : '';
      $end_time = isset($timestamp['end_time']) ? strtotime($timestamp['end_time']) : '';
      if (($start_time && $end_time) && $start_time > $end_time) {
        form_set_error('when][date_control][timestamps][stamps][' . $key, t('Date field is invalid. The end time is earlier than the beginning time.'));
      }

    }

  }

  // Validate weekscheme.
  if ($values['when']['date_control']['type'] == 'period' || $values['when']['date_control']['type'] == 'permanent') {

    $opening_times = $values['when']['date_control']['opening_times'];
    // Validate the weekscheme.
    if (!$opening_times['all_day']) {

      foreach ($opening_times['days'] as $day => $opening_times) {

        foreach ($opening_times as $key => $opening_time) {

          if (!empty($opening_time['open_from']) || !empty($opening_time['open_till'])) {

            $parents = array('when', 'date_control', 'opening_times', 'days', $day, $key);
            if (substr_count($opening_time['open_from'], ':') == 1) {
              $opening_time['open_from'] .= ':00';
              form_set_value(array('#parents' => array_merge($parents, array('open_from'))), $opening_time['open_from'], $form_state);
            }
            if (substr_count($opening_time['open_till'], ':') == 1) {
              $opening_time['open_till'] .= ':00';
              form_set_value(array('#parents' => array_merge($parents, array('open_till'))), $opening_time['open_till'], $form_state);
            }

            try {
              CultureFeed_Cdb_Data_Calendar::validateTime($opening_time['open_from']);
            }
            catch (Exception $e) {
              form_set_error('when][date_control][opening_times][days][' . $day . '][' . $key . '][open_from', t('Please fill in correct time.'));
            }

            try {
              CultureFeed_Cdb_Data_Calendar::validateTime($opening_time['open_till']);
            }
            catch (Exception $e) {
              form_set_error('when][date_control][opening_times][days][' . $day . '][' . $key . '][open_till', t('Please fill in correct time.'));
            }

          }

        }

      }

    }

  }

  // Validate copyright
  if (!empty($values['photo']['upload']) && $values['photo']['copyright'] != 1) {
    form_set_error('photo', t('Please agree to the general conditions of UiTdatabank and declare that you have the necessary rights or permissions to distribute the image through UiTdatabank.'));
  }
  if (!empty($values['photo']['upload']) && empty($values['photo']['copyright_text'])) {
    form_set_error('photo', t('Copyright field is required.'));
  }

  // Validate location
  $location = culturefeed_entry_ui_location_form_validate($form, $form_state);

  // Validate Links
  $i = 0;
  foreach ($values['links'] as $link_data) {
    if (!empty($link_data['URL'])) {
      if (!preg_match("@^https?://@", $link_data['URL'])) {
        $link_data['URL'] = 'http://' . $link_data['URL'];
      }
      if (!valid_url($link_data['URL'], TRUE)) {
        form_set_error('links][' . $i . '][URL', t('Not a valid URL'));
      }
    }
    $i++;
  }

  // Validate organiser.
  if (!empty($values['organiser']['actor']['organiser_actor_id'])) {

    try {
      $organiser = culturefeed_search_item_load($values['organiser']['actor']['organiser_actor_id'], 'actor');
      if (!$organiser) {
        form_set_error('organiser', t('We could not validate the organizer'));
      }
    } catch (Exception $e) {
      watchdog_exception('culturefeed_entry_ui', $e);
      form_set_error('organiser', t('We could not validate the organizer'));
    }

  }
  else {
    $organiser = NULL;
  }

  foreach ($values['wrapper'] as $extra) {
    if (is_array($extra)) {

      // Contacts
      if (!empty($extra['channel_input'])) {
        //if mail is selected
        if ($extra['channel'] == 1) {
          if (!valid_email_address($extra['channel_input'])) {
            form_set_error('channel_input', t('Not a valid email address'));
          }
        }
      }
    }
  }

  $errors = form_get_errors();
  if (empty($errors)) {
    _watersportkampen_bootstrap_event_form_save_event($form, $form_state, $location, $organiser);
  }
}

/*
* to save an event
 */
function _watersportkampen_bootstrap_event_form_save_event($form, &$form_state, CultuurNet\Search\ActivityStatsExtendedEntity $location = NULL, CultuurNet\Search\ActivityStatsExtendedEntity $organiser = NULL){

  $values = $form_state['values'];
  $performerList = new CultureFeed_Cdb_Data_PerformerList();
  $mails = array();
  $phones = array();
  $links = array();
  $performers_count = 0;

  $update = FALSE;
  if (isset($form['#event'])) {
    $update = TRUE;
    $event = $form['#event'];
  }
  else {
    $event = new CultureFeed_Cdb_Item_Event();
  }

  // Reset old tags.
  foreach ($values['old_tags'] as $tag => $keyword) {
    if (!in_array($tag, array_keys($values['tags']))) {
      $event->deleteKeyword(new CultureFeed_Cdb_Data_Keyword($keyword['value'], $keyword['visible']));
    }
  }

  // Categories.
  $category_options = array();

  $values['categories'] = "0.57.0.0.0";
  $category_options[$values['categories']] = culturefeed_search_get_eventtype_categories(array('tid' => $values['categories']));
  $categories = new CultureFeed_Cdb_Data_CategoryList();
  foreach ($category_options as $key => $value) {
    if ($value) {
      $categories->add(new CultureFeed_Cdb_Data_Category(CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_EVENT_TYPE, $key, $value[$key]));
    }
  }



  $event->setCategories($categories);
  //add sports to db

  foreach ($values['sports'] as $sport) {
    if (isset($sport) && $sport !== 0) {
      $values['tags'][$sport] = array(
        'value' => $sport,
        'visible' => TRUE
      );
    }
  }

  // Put new created tags in $form_state variable for further handling.
  $form_state['values']['tags'] = $values['tags'];

  foreach ($values['wrapper'] as $extra) {
    if (is_array($extra)) {
      // Performer
      if (!empty($extra['performer']) || !empty($extra['role'])) {
        $performer = new CultureFeed_Cdb_Data_Performer();
        $performer->setLabel($extra['performer']);
        $performer->setRole($extra['role']);
        $performerList->add($performer);
        $performers_count++;
      }

      // Contacts
      if (isset($extra['channel_input'])) {

        switch ($extra['channel']) {
          case '0':
            $phone = new CultureFeed_Cdb_Data_Phone($extra['channel_input'], CultureFeed_Cdb_Data_Phone::PHONE_TYPE_PHONE, FALSE, FALSE);
            array_push($phones, $phone);
            break;
          case '1':
            if ($extra['channel_input']) {
              $mail = new CultureFeed_Cdb_Data_Mail($extra['channel_input'], FALSE, FALSE);
              array_push($mails, $mail);
            }
            break;
        }
      }
    }
  }


  // Links
  $media_links = array();
  foreach ($values['links'] as $link_data) {

    if ($link_data['URL']) {

      if (!preg_match("@^https?://@", $link_data['URL'])) {
        $link_data['URL'] = 'http://' . $link_data['URL'];
      }

      $mediatype = CultureFeed_Cdb_Data_File::MEDIA_TYPE_WEBRESOURCE;

      if (strpos($link_data['URL'], 'plus.google.com')) {
        $mediatype = CultureFeed_Cdb_Data_File::MEDIA_TYPE_GOOGLEPLUS;
      }

      if (strpos($link_data['URL'], 'facebook.com')) {
        $mediatype = CultureFeed_Cdb_Data_File::MEDIA_TYPE_FACEBOOK;
      }

      if (strpos($link_data['URL'], 'twitter.com')) {
        $mediatype = CultureFeed_Cdb_Data_File::MEDIA_TYPE_TWITTER;
      }

      if (strpos($link_data['URL'], 'youtube.com') || strpos($link_data['URL'], 'youtu.be')) {
        $mediatype = CultureFeed_Cdb_Data_File::MEDIA_TYPE_YOUTUBE;
      }

      if ($link_data['reservation']) {

        // Make reservation link for contact element
        $link = new CultureFeed_Cdb_Data_Url($link_data['URL'], FALSE, $link_data['reservation']);
        array_push($links, $link);

        // Make reservation link for media element
        $mediatype = CultureFeed_Cdb_Data_File::MEDIA_TYPE_RESERVATIONS;

      }

      $link = new CultureFeed_Cdb_Data_File();
      $link->setHLink($link_data['URL']);
      $link->setMediaType($mediatype);
      if ($link_data['reservation']) {
        $link->setTitle(t('Order tickets'));
      }
      array_push($media_links, $link);
    }
  }

  // Age
  if ($values['age']) {
    $event->setAgeFrom(($values['age'] ? $values['age'] : 0));
  }
  else {
    // Age Category
    switch ($values['age_category']) {
      case '1-12':
        $event->setAgeFrom(1);
        break;
      case '12-18':
        $event->setAgeFrom(12);
        break;
      case '18+':
        $event->setAgeFrom(18);
        break;
      case 'everyone':
      default:
        break;
    }
  }

  // Timestamps.
  if ($values['when']['date_control']['type'] == 'timestamps') {
    _culturefeed_entry_ui_event_save_timestamps($event, $values['when']['date_control']['timestamps']['stamps']);
  }

  // Period.
  if ($values['when']['date_control']['type'] == 'period') {
    _culturefeed_entry_ui_event_save_period($event, $values['when']['date_control']['period']);
  }

  // Weekscheme.
  if ($values['when']['date_control']['type'] == 'period' || $values['when']['date_control']['type'] == 'permanent') {
    _culturefeed_entry_ui_event_save_weekscheme($event, $values['when']['date_control']);
  }

  // Event details.
  $detail = new CultureFeed_Cdb_Data_EventDetail();
  $detail->setTitle($values['title']);

  if (!empty($values['description']['sd']['short_description'])) {
    $detail->setShortDescription($values['description']['sd']['short_description']);
  }
  if (!empty($values['description']['ld']['long_description'])) {
    $detail->setLongDescription($values['description']['ld']['long_description']);
  }

  // Photo
  if ($values['photo']['upload']) {

    // Save to Drupal
    $drupal_file = file_load($values['photo']['upload']);
    $drupal_file->status = FILE_STATUS_PERMANENT;
    file_save($drupal_file);
    file_usage_add($drupal_file, 'culturefeed_entry_ui', 'event', $drupal_file->fid);

    // Add to detail
    $file = new CultureFeed_Cdb_Data_File();
    $file->setMediaType($file::MEDIA_TYPE_PHOTO);
    $file->setCopyright($values['photo']['copyright_text']);
    $file->setMain(TRUE);
    switch ($drupal_file->filemime) {
      case 'image/gif':
        $file->setFileType($file::FILE_TYPE_GIF);
        break;
      case 'image/jpg':
      case 'image/jpeg':
        $file->setFileType($file::FILE_TYPE_JPEG);
        break;
      case 'image/png':
        $file->setFileType($file::FILE_TYPE_PNG);
        break;
    }
    $file->setHLink(file_create_url($drupal_file->uri));
    $file->setFilename($drupal_file->filename);

    $detail->getMedia()->add($file);
    // Remove old image
    // Bug on UDB ... will be fixed soon
    if (isset($values['photo']['current_file_hlink'])) {
      try {
        Drupalculturefeed_EntryApi::removeLinkFromEvent($event, $values['photo']['current_file_hlink']);

      } catch (Exception $e) {
        watchdog_exception('culturefeed_entry_ui', $e);
      }
    }
  }

  // Media links
  if ($media_links) {
    foreach ($media_links as $media_link) {
      $detail->getMedia()->add($media_link);
    }
  }


  // Price
  if ($values['price']['free']) {
    $price = new CultureFeed_Cdb_Data_Price(0);
    $detail->setPrice($price);
  }
  else {
    if (!empty($values['price']['amount'])) {
      $price = new CultureFeed_Cdb_Data_Price(floatval(str_replace(',','.', $values['price']['amount'])));
      if (!empty($values['price']['extra']['extra_info'])) {
        $price->setDescription($values['price']['extra']['extra_info']);
      }
      $detail->setPrice($price);
    }
  }

  // Performers
  if ($performers_count) {
    $detail->setPerformers($performerList);
  }

  $detail->setLanguage(culturefeed_entry_ui_get_preferred_language());

  $details = new CultureFeed_Cdb_Data_EventDetailList();
  $details->add($detail);

  // Translations Dutch.
  if (culturefeed_search_get_preferred_language() != 'nl') {
    if ($values['dutch']['language'] || $values['dutch']['short_description'] || $values['dutch']['long_description']) {

      $detail = new CultureFeed_Cdb_Data_EventDetail();
      if ($values['dutch']['language']) {
        $detail->setTitle($values['dutch']['language']);
      }
      if (!empty($values['dutch']['short_description'])) {
        $detail->setShortDescription($values['dutch']['short_description']);
      }
      if (!empty($values['dutch']['long_description'])) {
        $detail->setLongDescription($values['dutch']['long_description']);
      }

      $detail->setLanguage("nl");
      $details->add($detail);
    }
  }

  $event->setDetails($details);

  // Location.
  $address = culturefeed_entry_ui_location_form_save($event, $location, $form_state);

  // Event organiser.
  if ($organiser) {
    $organiser_object = new CultureFeed_Cdb_Data_Organiser();
    $organiser_detail = $organiser->getEntity()->getDetails()
      ->getDetailByLanguage(culturefeed_search_get_preferred_language());
    if (!$organiser_detail) {
      $organiser_detail = $organiser->getEntity()->getDetails()
        ->getDetailByLanguage("nl");
    }
    $organiser_object->setLabel($organiser_detail->getTitle());
    $organiser_object->setCdbid($organiser->getEntity()->getCdbId());
    $event->setOrganiser($organiser_object);
  }
  else {
    if (isset($values['organiser']['new_actor'])) {
      $organiser_object = new CultureFeed_Cdb_Data_Organiser();
      $organiser_object->setLabel($values['organiser']['new_actor']);
      $event->setOrganiser($organiser_object);
    }
  }

  // Contact info.
  $physical_address = $address->getPhysicalAddress();
  $contact_object = new CultureFeed_Cdb_Data_ContactInfo();
  $contact_object->addAddress(new CultureFeed_Cdb_Data_Address($physical_address));

  foreach ($mails as $mail) {
    $contact_object->addMail($mail);
  }
  foreach ($phones as $phone) {
    $contact_object->addPhone($phone);
  }
  foreach ($links as $link) {
    $contact_object->addUrl($link);
  }

  $event->setContactInfo($contact_object);

  // Keywords.
  culturefeed_entry_ui_tags_form_save($event, $form_state);

  // Members.
  if ($values['members']) {
    $event->setPrivate(TRUE);
  }

  $form_state['submit_time'] = time();

  try {
    if ($update) {
      Drupalculturefeed_EntryApi::updateEvent($event);
      $form_state['#event_id'] = $event->getCdbId();
      watchdog('culturefeed_entry_ui', 'Event %eventid updated.', array('%eventid' => $form_state['#event_id']));
      cache_clear_all('culturefeed:results:detail:event:' . $event->getCdbId(), 'cache_culturefeed_search');
    }
    else {
      $form_state['#event_id'] = Drupalculturefeed_EntryApi::createEvent($event);
      watchdog('culturefeed_entry_ui', 'Event %eventid created.', array('%eventid' => $form_state['#event_id']));
    }

    $form_state['#update_event'] = $update;

  } catch (Exception $e) {
    watchdog_exception('culturefeed_entry_ui', $e);
    form_set_error('', t('An error occurred while saving the event'));
  }

  // Delete files from file system
  if ($form_state['values']['photo']['upload']) {
    $file = file_load($form_state['values']['photo']['upload']);
    file_delete($file);
  }
  if (isset($form_state['values']['photo']['current_file'])) {
    $file = file_load($form_state['values']['photo']['current_file']);
    file_delete($file);
  }

}

function watersportkampen_bootstrap_form_culturefeed_entry_ui_tags_form_alter(array &$form, array &$form_state, \CultureFeed_Cdb_Item_Event $event = NULL) {
  // Default values.
  $keywords = array();

  $sports = _watersportkampen_bootstrap_get_sports();

  if ($event && $event->getKeywords(TRUE)) {
    /* @var \CultureFeed_Cdb_Data_Keyword $keyword */
    foreach ($event->getKeywords(TRUE) as $keyword) {
      $kw = $keyword->getValue();
      if (!in_array($kw, $sports)) {
          if ($kw !== 'watersportkampen') {
            $keywords[$keyword->getValue()] = array(
              'value' => $keyword->getValue(),
              'visible' => $keyword->isVisible(),
            );
          }
      }
    }


  }

  // Form element.
  $form['extra']['tags'] = array(
    '#type' => 'culturefeed_tags_element',
    '#title' => t('Tags'),
    '#description' => t('Add Add tags'),
    '#default_value' => $keywords,
  );

  $form['extra']['old_tags'] = array(
    '#type' => 'value',
    '#value' => $keywords,
  );
}
