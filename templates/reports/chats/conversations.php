<div class="oo-chat-conversations reports">
  <ul class="oo-session-list">
    <?php
    $report = new OOReport();
    $ses = new OOSession();
    $sessions = $report->get_sessions(array('number'=>10));
    foreach ($sessions as $key => $session) {
      $session_data = $ses->get($session->ses_id);
			$html .= $session_data->render();
    }
    echo $html;
    ?>

  </ul>
</div>
