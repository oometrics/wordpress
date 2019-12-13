<?php
$settings = get_option('oometrics_options');

$args = array(
    'posts_per_page'   => -1,
    'orderby'          => 'title',
    'order'            => 'asc',
    'post_type'        => 'shop_coupon',
    'post_status'      => 'publish',
);

$coupons = get_posts( $args );
?>
<input id="oo_ses_id" value="-1" type="hidden"/>
<div class="wrap">
  <div class="oo-dashboard-wrapper">
    <div class="oo-dashboard-header">
    </div>
    <div class="oo-dashboard-main">
      <div class="oo-dashboard-left">
        <div class="oo-dashboard-left-left oo-chat">
          <div class="oo-push-overlay hide">
            <div class="oo-inner">
              <header>
                <select id="oo-choose-push">
                  <option value=""><?php _e('Choose a type','oometrics');?></option>
                  <option value="sale_price"><?php _e('Product sale price','oometrics');?></option>
                  <option value="apply_coupon"><?php _e('Apply a coupon','oometrics');?></option>
                  <option value="open_popup"><?php _e('Open a popup','oometrics');?></option>
                </select>
              </header>
              <div class="oo-push-options">
                <div class="oo-push-option active" id="default">
                  <p><?php _e('You need to choose a push type from the above to see the optoins','oometrics');?></p>
                </div>
                <div class="oo-push-option" id="sale_price">
                  <p><?php _e('You can push sales price to any products you chose and push it to the customer session. This push will only be visible for this session. No body else can see these changes!','oometrics');?></p>
                  <div class="form-field">
                    <div class="oo-search-field">
                      <label for="oo_product_id"><?php _e('Product','oometrics');?></label>
                      <input type="text" id="oo_product_search" class="oo-product-search" placeholder="<?php _e('Type to search','oometrics');?>"/>
                      <input type="hidden" id="oo_product_id"/>
                      <div class="oo-search-results"></div>
                      <div class="oo-search-selected"></div>
                    </div>
                    <label for="oo_sale_amount"><?php _e('Price amount','oometrics');?></label>
                    <input type="text" id="oo_sale_amount" placeholder="$"/>
                    <label for="oo_sale_percent"><?php _e('Price percent','oometrics');?></label>
                    <input type="text" id="oo_sale_percent" placeholder="%"/>
                    <p><?php _e('You should fill only one item,percent or amount.','oometrics');?></p>
                  </div>
                </div>
                <div class="oo-push-option" id="apply_coupon">
                  <div class="form-field">
                    <label for="oo_coupon_id"><?php _e('Coupon','oometrics');?></label>
                    <select id="oo-coupons">
                      <option value=""><?php _e('Choose a coupon','oometrics');?></option>
                      <?php
                      foreach ($coupons as $key => $coupon) {
                        ?>
                        <option value="<?php echo $coupon->post_title;?>"><?php echo $coupon->post_title;?></option>
                        <?php
                      }
                       ?>
                    </select>

                  </div>
                </div>


                <div class="oo-push-option" id="open_popup">
                  <div class="form-field">
                    <label for="oo_popup_type"><?php _e('Popup type','oometrics');?></label>
                    <select id="oo_popup_type">
                      <option value=""><?php _e('Choose a type','oometrics');?></option>
                      <option value="promotional"><?php _e('Promotional','oometrics');?></option>
                      <option value="ooarea"><?php _e(' OOArea Widget Sidebar','oometrics');?></option>
                    </select>

                    <?php wp_editor( $content, 'oo-popup-text',array('textarea_rows'=>2,'teeny'=>true));?>
                    <div class="oo-popup-actions">
                      <label for="oo_popup_btn_1_label"><?php _e('Primary button label','oometrics');?></label>
                      <input type="text" id="oo_popup_btn_1_label" class="oo-popup-field" placeholder="<?php _e('call to action','oometrics');?>"/>
                      <label for="oo_popup_btn_1_href"><?php _e('Primary button link','oometrics');?></label>
                      <input type="text" id="oo_popup_btn_1_href" class="oo-popup-field" placeholder="<?php _e('http://','oometrics');?>"/>

                      <label for="oo_popup_btn_2_label"><?php _e('Primary button label','oometrics');?></label>
                      <input type="text" id="oo_popup_btn_2_label" class="oo-popup-field" placeholder="<?php _e('alternative action, read more','oometrics');?>"/>
                      <label for="oo_popup_btn_2_href"><?php _e('Primary button link','oometrics');?></label>
                      <input type="text" id="oo_popup_btn_2_href" class="oo-popup-field" placeholder="<?php _e('http://','oometrics');?>"/>


                    </div>
                  </div>
                </div>
                <div class="form-field push-duration">
                  <select id="oo_push_duration">
                    <option value=""><?php _e('Choose a duration','oometrics');?></option>
                    <option value="end"><?php _e('End of session','oometrics');?></option>
                    <option value="fivemin"><?php _e('5 Minutes','oometrics');?></option>
                    <option value="tenmin"><?php _e(' 10 Minutes','oometrics');?></option>
                    <option value="onehour"><?php _e(' 1 Hour','oometrics');?></option>
                  </select>
                </div>

              </div>
            </div>
            <button  id="oo-send-the-push" class="button button-hero" href="#"><?php _e('Push to the session','oometrics');?></button>
            <button  id="oo-close-send-the-push" class="button button-link" href="#"><img src="<?php echo OOMETRICS_URL;?>/assets/images/close-popup.svg"/></button>
          </div>
          <div class="oo-dashboard-left-left-header">
            <?php require_once(OOMETRICS_PATH.'/templates/dashboard/chats/profile-info.php'); ?>
          </div>
          <div class="oo-dashboard-left-left-body">
            <?php require_once(OOMETRICS_PATH.'/templates/dashboard/chats/conversations.php'); ?>
          </div>
          <div class="oo-dashboard-left-reply">
            <div class="oo-dashboard-reply hide">
              <p class="oo-message"><?php _e('You need to choose a session to start','oometrics');?> ;)</p>
              <div class="oo-overlay hide"><a href="#" class="button button button-default button-hero start-new-chat"><?php _e('Pop new conversation','oometrics');?></a></div>
              <div class="oo-overlay block"><a href="#" class="button button button-primary button-hero start-new-conv"><?php _e('Choose or start new','oometrics');?></a></div>
              <div class="oo-dashboard-reply-inner">
                  <?php if($settings['chat_editor'] == 'tinyMCE') {?>
                  <?php wp_editor( $content, 'oo-message-text',array('textarea_rows'=>1,'teeny'=>true));?>
                <?php } else { ?>
                  <textarea id="oo-message-text" name="oo_message_content" rows="3" placeholder="<?php _e('Start typing','oometrics');?>"></textarea>
                <?php } ?>
                <div class="oo-reply-actions">
                  <button type="button" id="oo-send-message" class="button button-hero"><?php _e('Send','oometrics');?></button>
                  <button type="button" id="oo-open-push-to-session" class="button button-hero align-right"><img src="<?php echo OOMETRICS_URL;?>/assets/images/session-push.svg"/><?php _e('Push to session','oometrics');?></button>
                </div>

              </div>
            </div>
          </div>
        </div>
        <div class="oo-dashboard-left-right">
          <div class="oo-dashboard-left-right-header">
            <?php require_once(OOMETRICS_PATH.'/templates/dashboard/info/header.php'); ?>
          </div>
          <div class="oo-dashboard-left-right-body oo-tab-content">
            <?php require_once(OOMETRICS_PATH.'/templates/dashboard/info/digital-tab.php'); ?>
          </div>
          <div class="oo-dashboard-left-right-footer">
            <?php if ( class_exists( 'WooCommerce' ) ) { require_once(OOMETRICS_PATH.'/templates/dashboard/info/cart.php');} ?>
          </div>
          <div class="oo-cart-overlay hide">
            <div class="oo-inner">
            <div class="oo-notification"></div>
              <div class="form-field">
                <div class="oo-search-field">
                  <label for="oo_product_id"><?php _e('Product','oometrics');?></label>
                  <input type="text" id="oo_product_search" class="oo-product-search" placeholder="<?php _e('Type to search','oometrics');?>"/>
                  <input type="hidden" id="oo_product_id"/>
                  <div class="oo-search-results"></div>
                  <div class="oo-search-selected"></div>
                </div>
              </div>
              <div class="oo-current-cart-items">
                <p><?php _e('Choose a session to show cart content','oometrics');?></p>
              </div>
              <div class="oo-update-cart-changes">
                <a href="#" id="oo_change_cart" class="button button-primary"><?php _e('Update changes','oometrics');?></a><br />
                <span><em><?php _e('please be sure','oometrics');?></em>, <em><?php _e('customer may panick!','oometrics');?></em><br /><em><?php _e('you can use chat to inform','oometrics');?></em></span><br />
              </div>
            </div>
          </div>

        </div>

      </div>
      <div class="oo-dashboard-sidebar">
        <?php
          $sidebar_ses_obj = new OOSession();
          $total_sale = $sidebar_ses_obj->get_total_sales_day();
          $sessions = $sidebar_ses_obj->get_live();
          $online_users = $sidebar_ses_obj->get_online();
          $unique_users = $sidebar_ses_obj->get_unique_users();
          $pageviews = $sidebar_ses_obj->get_pageviews();
         ?>
        <div class="oo-dashboard-sidebar-header">
          <?php require_once(OOMETRICS_PATH.'/templates/dashboard/sidebar/header.php'); ?>
        </div>
        <div class="oo-dashboard-sidebar-body">
          <?php require_once(OOMETRICS_PATH.'/templates/dashboard/sidebar/body.php'); ?>
        </div>
        <div class="oo-dashboard-sidebar-footer">
          <?php require_once(OOMETRICS_PATH.'/templates/dashboard/sidebar/footer.php'); ?>
        </div>
      </div>
    </div>
    <div class="oo-dashboard-footer">
      <ul class="oo-nav list">
        <li class="oo-footer-logo"><a href="#"><img width="100" src="<?php echo OOMETRICS_URL;?>assets/images/oometrics-logo.svg"/></a></li>
        <li><a class="button button-link" href="<?php echo admin_url('admin.php?page=oometrics-reports');?>"><?php _e('Reports', 'oometrics'); ?></a></li>
        <li><a class="button button-link" href="<?php echo admin_url('admin.php?page=oometrics-settings');?>"><?php _e('Settings', 'oometrics'); ?></a></li>
      </ul>
      <!-- <ul class="oo-nav list right">
        <li><a target="_blank" class="button button-link" href="http://oometrics.com/faqs"><?php _e('FAQs', 'oometrics'); ?></a></li>
        <li><a target="_blank" class="button button-link" href="http://oometrics.com/support"><?php _e('Support', 'oometrics'); ?></a></li>
        <li><a target="_blank" class="oo-get-pro button button-default button-small" href="http://oometrics.com/donate"><strong>🤘 <?php _e('Donate!', 'oometrics'); ?></strong></a></li>
      </ul> -->
    </div>
  </div>

</div>
