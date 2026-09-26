# Settings coverage index

This index groups matching parameter names across Academy, Blog, and Codex. A rendered value means the request completed; it does not prove the setting changes output. Static references can be dynamic or framework-managed. See AUDIT.md for behavior checks and limitations.

| Parameter | Field types | Sections | Render cases | Static PHP/JS consumers |
|---|---|---|---:|---:|
| `accordion_bootstrap_color` | list | accordion_settings | 24 | 3 |
| `accordion_color_mode` | list | accordion_settings | 6 | 3 |
| `accordion_custom_color` | color | accordion_settings | 0 | 3 |
| `accordion_template` | list | accordion_settings | 24 | 3 |
| `ai_claude_clear` | checkbox | ai_tools | 0 | 0 |
| `ai_claude_key` | password | ai_tools | 0 | 0 |
| `ai_claude_model` | neuralnetworkmodel | ai_tools | 0 | 3 |
| `ai_claude_model_custom` | text | ai_tools | 0 | 0 |
| `ai_enabled` | list | ai_tools | 0 | 6 |
| `ai_hourly_limit` | number | ai_tools | 0 | 3 |
| `ai_image_model` | neuralnetworkmodel | ai_tools | 0 | 3 |
| `ai_image_model_custom` | text | ai_tools | 0 | 0 |
| `ai_images` | list | ai_tools | 0 | 6 |
| `ai_max_tokens` | number | ai_tools | 0 | 3 |
| `ai_openai_clear` | checkbox | ai_tools | 0 | 0 |
| `ai_openai_key` | password | ai_tools | 0 | 0 |
| `ai_openai_model` | neuralnetworkmodel | ai_tools | 0 | 3 |
| `ai_openai_model_custom` | text | ai_tools | 0 | 0 |
| `ai_permissions_note` | note | ai_tools | 0 | 0 |
| `ai_provider` | list | ai_tools | 0 | 3 |
| `campaign_from_email` | email | email_delivery | 0 | 3 |
| `campaign_from_name` | text | email_delivery | 0 | 3 |
| `campaign_mail_service` | list | email_delivery | 18 | 3 |
| `campaign_mailgun_domain` | text | email_delivery | 0 | 3 |
| `campaign_mailgun_key` | password | email_delivery | 0 | 3 |
| `campaign_mailgun_region` | list | email_delivery | 6 | 3 |
| `campaign_postmark_key` | password | email_delivery | 0 | 3 |
| `campaign_postmark_stream` | text | email_delivery | 0 | 3 |
| `campaign_reply_to` | email | email_delivery | 0 | 3 |
| `campaign_sendgrid_key` | password | email_delivery | 0 | 3 |
| `campaign_smtp_host` | text | email_delivery | 0 | 3 |
| `campaign_smtp_password` | password | email_delivery | 0 | 3 |
| `campaign_smtp_port` | number | email_delivery | 9 | 3 |
| `campaign_smtp_security` | list | email_delivery | 9 | 3 |
| `campaign_smtp_user` | text | email_delivery | 0 | 3 |
| `cancel_redirect_menuitem` | modal_menu | basic | 3 | 3 |
| `captcha` | plugins | editinglayout | 3 | 6 |
| `categories_column_style` | list | categories, request | 12 | 3 |
| `categories_columns` | list | categories, request | 30 | 3 |
| `categories_description` | textarea | basic | 0 | 0 |
| `categories_listing_layout` | list | categories, request | 12 | 3 |
| `categories_style` | list | categories, request | 12 | 3 |
| `category_layout` | componentlayout | category, category_layout_options, request | 12 | 9 |
| `catid` | postcategory | basic, request | 0 | 123 |
| `column_bootstrap_color` | list | column_settings | 24 | 3 |
| `column_color_mode` | list | column_settings | 6 | 3 |
| `column_custom_color` | color | column_settings | 0 | 3 |
| `column_gap` | list | column_settings | 18 | 3 |
| `column_style` | list | list_display, request | 18 | 6 |
| `column_template` | list | column_settings | 24 | 3 |
| `column_vertical_align` | list | column_settings | 12 | 3 |
| `columns_per_row` | list | list_display, request | 45 | 6 |
| `comments_cooldown` | number | comment_safety | 9 | 3 |
| `comments_moderation` | radio | comments | 6 | 3 |
| `comments_notify` | radio | comment_safety | 6 | 3 |
| `comments_provider` | list | comments | 27 | 3 |
| `compact_columns` | list | compact_posts | 69 | 3 |
| `compact_layout` | list | compact_posts | 33 | 3 |
| `compact_selection` | list | compact_posts | 69 | 15 |
| `compact_show` | list | compact_posts | 33 | 3 |
| `compact_show_image` | list | compact_posts | 33 | 6 |
| `compact_show_rating` | list | compact_posts | 33 | 6 |
| `compact_show_title` | list | compact_posts | 33 | 6 |
| `custom_cancel_redirect` | radio | basic | 6 | 3 |
| `custom_fields_enable` | radio | integration_customfields | 6 | 0 |
| `date_format` | text | advanced, list_default_parameters | 0 | 3 |
| `date_type` | list | basic, post, posts | 69 | 27 |
| `default_editor_mode` | list | block_editor | 6 | 3 |
| `default_post_redirect_category` | postcategory | create_post_redirect | 0 | 3 |
| `default_post_redirect_menuitem` | modal_menu | create_post_redirect | 3 | 3 |
| `default_post_redirect_type` | list | create_post_redirect | 18 | 3 |
| `display_num` | list | advanced, basic, list_default_parameters | 108 | 6 |
| `disqus_shortname` | text | comments | 0 | 3 |
| `email_link_tracking` | radio | email_tracking | 6 | 6 |
| `email_open_tracking` | radio | email_tracking | 6 | 6 |
| `enable_category` | radio | basic | 6 | 9 |
| `engagement_ratings` | radio | engagement | 6 | 3 |
| `engagement_sharing` | radio | engagement | 6 | 3 |
| `engagement_subscribe` | radio | engagement | 6 | 3 |
| `featured_image_class` | text | editinglayout | 0 | 6 |
| `featured_slider_all_pages` | list | featured_slider | 18 | 3 |
| `featured_slider_author` | list | featured_slider | 18 | 0 |
| `featured_slider_auto` | list | featured_slider | 18 | 0 |
| `featured_slider_avatar` | list | featured_slider | 18 | 0 |
| `featured_slider_category` | list | featured_slider | 18 | 0 |
| `featured_slider_content` | list | featured_slider | 18 | 0 |
| `featured_slider_content_length` | number | featured_slider | 27 | 3 |
| `featured_slider_count` | number | featured_slider | 27 | 3 |
| `featured_slider_date` | list | featured_slider | 18 | 3 |
| `featured_slider_date_source` | list | featured_slider | 27 | 3 |
| `featured_slider_enabled` | list | featured_slider | 18 | 3 |
| `featured_slider_image` | list | featured_slider | 18 | 0 |
| `featured_slider_interval` | number | featured_slider | 27 | 6 |
| `featured_slider_navigation` | list | featured_slider | 18 | 0 |
| `featured_slider_ratings` | list | featured_slider | 18 | 0 |
| `featured_slider_readmore` | list | featured_slider | 18 | 0 |
| `featured_slider_style` | list | featured_slider | 63 | 3 |
| `featured_slider_title` | list | featured_slider | 18 | 0 |
| `feed_show_readmore` | radio | integration_newsfeed | 6 | 6 |
| `feed_summary` | list | integration, integration_newsfeed | 30 | 6 |
| `filter_field` | list | advanced, basic, list_default_parameters | 54 | 9 |
| `filter_tag` | posttags | request | 0 | 18 |
| `flags` | radio | posts | 6 | 6 |
| `history_limit` | number | editinglayout | 9 | 0 |
| `hypercomments_widget_id` | text | comments | 0 | 3 |
| `id` | hidden, modal_post, postcategory | request | 0 | 282 |
| `info_block_show_title` | list, radio | basic, post, posts | 36 | 3 |
| `intensedebate_account` | text | comments | 0 | 3 |
| `items_limit_source` | list | list_display, request | 96 | 6 |
| `layout_type` | hidden | advanced, basic | 0 | 9 |
| `link_author` | list, radio | basic, post, posts | 48 | 24 |
| `link_category` | list, radio | basic, post, posts | 48 | 18 |
| `link_featured_image` | list, radio | compact_posts | 18 | 15 |
| `link_parent_category` | list, radio | basic, post, posts | 48 | 6 |
| `link_titles` | list, radio | basic, post, posts | 48 | 15 |
| `list_author` | author | advanced | 0 | 3 |
| `list_author_filtering_type` | list | advanced | 18 | 3 |
| `list_excerpt_length` | number | automated_truncation | 63 | 3 |
| `list_item_style` | list | list_display, request | 30 | 27 |
| `list_show_author` | list, radio | advanced, list_default_parameters | 18 | 3 |
| `list_show_hits` | list, radio | advanced, list_default_parameters | 18 | 3 |
| `list_show_ratings` | votelist, voteradio | advanced, list_default_parameters | 12 | 3 |
| `list_show_votes` | votelist, voteradio | advanced, list_default_parameters | 12 | 3 |
| `listing_authors` | postauthors | listing_filters, request | 0 | 3 |
| `listing_canonical` | url | listing_filters, request | 0 | 3 |
| `listing_categories` | postcategory | listing_filters, request | 0 | 30 |
| `listing_exclude_authors` | postauthors | listing_filters, request | 0 | 3 |
| `listing_exclude_categories` | postcategory | listing_filters, request | 0 | 3 |
| `listing_include_featured` | list | listing_filters, request | 18 | 9 |
| `listing_pin_featured` | list | listing_filters, request | 18 | 6 |
| `listing_subcategories` | list | listing_filters, request | 18 | 6 |
| `listing_tags` | posttags | listing_filters, request | 0 | 9 |
| `maxLevel` | list | basic, category | 84 | 6 |
| `maxLevelcat` | list | basic, categories | 39 | 0 |
| `num_links` | number | compact_posts | 36 | 18 |
| `order_date` | list | advanced, basic, blog, shared | 60 | 12 |
| `orderby_pri` | list | advanced, blog, shared | 60 | 6 |
| `orderby_sec` | list | advanced, basic, blog, shared | 270 | 15 |
| `poll_guest_voting` | radio | poll_access | 6 | 3 |
| `poll_progress_color_mode` | list | polls | 9 | 3 |
| `poll_progress_custom_color` | color | polls | 0 | 3 |
| `poll_progress_labels` | radio | polls | 6 | 3 |
| `poll_progress_striped` | radio | polls | 6 | 3 |
| `poll_results_style` | list | polls | 9 | 3 |
| `poll_show_votes` | radio | polls | 6 | 3 |
| `post_layout` | componentlayout | basic, post, posts | 24 | 6 |
| `post_listing_layout` | list | list_display, request | 18 | 6 |
| `posts_per_page` | number | blog, list_display, request | 36 | 9 |
| `quote_bootstrap_color` | list | quote_settings | 24 | 3 |
| `quote_color_mode` | list | quote_settings | 6 | 3 |
| `quote_custom_color` | color | quote_settings | 0 | 3 |
| `quote_template` | list | quote_settings | 18 | 3 |
| `rating_label` | text | engagement_appearance | 0 | 3 |
| `readmore_limit` | number | posts | 9 | 3 |
| `record_hits` | radio | posts | 6 | 6 |
| `redirect_menuitem` | modal_menu | basic | 3 | 3 |
| `rules` | rules | permissions | 0 | 9 |
| `save_history` | radio | editinglayout | 6 | 6 |
| `sef_ids` | radio | integration_sef | 6 | 3 |
| `share_email` | radio | engagement_appearance | 6 | 3 |
| `share_facebook` | radio | engagement_appearance | 6 | 6 |
| `share_linkedin` | radio | engagement_appearance | 6 | 6 |
| `share_pinterest` | radio | engagement_appearance | 6 | 6 |
| `share_x` | radio | engagement_appearance | 6 | 6 |
| `show_associations` | assoc, radio | basic, post, posts | 33 | 24 |
| `show_associations_edit` | radio | editinglayout | 6 | 3 |
| `show_author` | list, radio | basic, post, posts | 48 | 33 |
| `show_base_description` | list, radio | basic, categories | 12 | 0 |
| `show_cat_num_posts` | list, radio | basic, category | 24 | 12 |
| `show_cat_num_posts_cat` | list, radio | basic, categories | 12 | 9 |
| `show_cat_tags` | list, radio | basic, category | 18 | 0 |
| `show_category` | list, radio | basic, post, posts | 48 | 33 |
| `show_category_heading_title_text` | list, radio | basic, category | 18 | 3 |
| `show_category_title` | list, radio | basic, category | 24 | 6 |
| `show_configure_edit_options` | radio | editinglayout | 6 | 3 |
| `show_date` | list, radio | basic, post, posts | 48 | 27 |
| `show_description` | list, radio | basic, category | 24 | 6 |
| `show_description_image` | list, radio | basic, category | 24 | 9 |
| `show_empty_categories` | list, radio | basic, category | 24 | 9 |
| `show_empty_categories_cat` | list, radio | basic, categories | 12 | 9 |
| `show_featured` | list | advanced | 9 | 9 |
| `show_feed_link` | list, radio | integration, integration_newsfeed | 30 | 15 |
| `show_headings` | list, radio | advanced, list_default_parameters | 18 | 3 |
| `show_hits` | list, radio | basic, post, posts | 48 | 21 |
| `show_intro` | list, radio | basic, post, posts | 48 | 9 |
| `show_item_navigation` | list, radio | basic, post, posts | 48 | 3 |
| `show_no_posts` | list, radio | basic, category | 24 | 9 |
| `show_noauth` | list, radio | basic, post, posts | 42 | 30 |
| `show_pagination` | list | advanced, shared | 45 | 9 |
| `show_pagination_limit` | list, radio | advanced, list_default_parameters | 18 | 3 |
| `show_pagination_results` | list, radio | advanced, shared | 30 | 12 |
| `show_parent_category` | list, radio | basic, post, posts | 48 | 21 |
| `show_permissions` | radio | editinglayout | 6 | 3 |
| `show_post_options` | radio | editinglayout | 6 | 3 |
| `show_postnav` | list, radio | basic, pagevars, post, postnav, posts | 66 | 3 |
| `show_powered_by` | radio | postnav | 6 | 3 |
| `show_publishing_options` | radio | editinglayout | 6 | 6 |
| `show_readmore` | list, radio | post, posts | 30 | 12 |
| `show_readmore_title` | list, radio | post, posts | 30 | 3 |
| `show_subcat_desc` | list, radio | basic, category | 24 | 9 |
| `show_subcat_desc_cat` | list, radio | basic, categories | 12 | 3 |
| `show_subcategory_content` | list | blog | 21 | 9 |
| `show_tags` | list, radio | basic, post, posts | 27 | 12 |
| `show_title` | list, radio | basic, post, posts | 42 | 15 |
| `show_urls_images_backend` | radio | editinglayout | 6 | 3 |
| `show_urls_images_frontend` | radio | editinglayout | 6 | 3 |
| `show_vote` | votelist, voteradio | basic, post, posts | 42 | 3 |
| `show_wiki_details` | list, radio | basic, post, posts | 36 | 3 |
| `spacer1` | spacer | advanced, posts | 0 | 0 |
| `spacer2` | spacer | posts | 0 | 0 |
| `spacer3` | spacer | editinglayout, posts | 0 | 0 |
| `spacer4` | spacer | editinglayout | 0 | 0 |
| `spacer5` | spacer | blog | 0 | 0 |
| `subcategories_column_style` | list | basic, category | 18 | 3 |
| `subcategories_columns` | list | basic, category | 45 | 3 |
| `subcategories_listing_layout` | list | basic, category | 18 | 3 |
| `subcategories_style` | list | basic, category | 18 | 3 |
| `subscribe_button` | text | engagement_appearance | 0 | 9 |
| `subscribe_consent` | textarea | engagement_appearance | 0 | 9 |
| `subscribe_heading` | text | engagement_appearance | 0 | 9 |
| `subscribe_text` | textarea | engagement_appearance | 0 | 9 |
| `summary_limit` | number | basic | 9 | 0 |
| `tab_bootstrap_color` | list | tab_settings | 24 | 3 |
| `tab_color_mode` | list | tab_settings | 6 | 3 |
| `tab_custom_color` | color | tab_settings | 0 | 3 |
| `tab_mode` | list | tab_settings | 6 | 3 |
| `tab_template` | list | tab_settings | 18 | 3 |
| `tag_id` | posttags | request | 0 | 15 |
| `truncation_accordion_position` | list | automated_truncation | 63 | 0 |
| `truncation_alert_position` | list | automated_truncation | 63 | 0 |
| `truncation_audio_position` | list | automated_truncation | 63 | 0 |
| `truncation_button_position` | list | automated_truncation | 63 | 0 |
| `truncation_columns_position` | list | automated_truncation | 63 | 0 |
| `truncation_comparison_position` | list | automated_truncation | 63 | 0 |
| `truncation_content_override` | radio | automated_truncation | 36 | 0 |
| `truncation_embed_position` | list | automated_truncation | 63 | 0 |
| `truncation_enabled` | list | automated_truncation | 42 | 3 |
| `truncation_gallery_position` | list | automated_truncation | 63 | 0 |
| `truncation_image_position` | list | automated_truncation | 63 | 0 |
| `truncation_paragraph_override` | radio | automated_truncation | 36 | 0 |
| `truncation_paragraphs` | number | automated_truncation | 63 | 3 |
| `truncation_poll_position` | list | automated_truncation | 63 | 0 |
| `truncation_quote_position` | list | automated_truncation | 63 | 0 |
| `truncation_readmore` | list | automated_truncation | 42 | 3 |
| `truncation_rule_position` | list | automated_truncation | 63 | 0 |
| `truncation_section_position` | list | automated_truncation | 63 | 0 |
| `truncation_tabs_position` | list | automated_truncation | 63 | 0 |
| `truncation_type` | list | automated_truncation | 84 | 3 |
| `truncation_video_position` | list | automated_truncation | 63 | 0 |
| `urls_position` | list | basic, posts | 18 | 0 |
| `year_sort_order` | list | basic | 6 | 3 |
