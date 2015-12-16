<?php
/*##################################################
 *                               WebFormController.class.php
 *                            -------------------
 *   begin                : August 21, 2014
 *   copyright            : (C) 2014 Julien BRISWALTER
 *   email                : julienseth78@phpboost.com
 *
 *
 ###################################################
 *
 * This program is a free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 ###################################################*/

 /**
 * @author Julien BRISWALTER <julienseth78@phpboost.com>
 */

class WebFormController extends ModuleController
{
	/**
	 * @var HTMLForm
	 */
	private $form;
	/**
	 * @var FormButtonSubmit
	 */
	private $submit_button;
	
	private $lang;
	private $common_lang;
	
	private $weblink;
	private $is_new_weblink;
	
	public function execute(HTTPRequestCustom $request)
	{
		$this->init();
		
		$this->check_authorizations();
		
		$this->build_form($request);
		
		$tpl = new StringTemplate('# INCLUDE FORM #');
		$tpl->add_lang($this->lang);
		
		if ($this->submit_button->has_been_submited() && $this->form->validate())
		{
			$this->save();
			$this->redirect();
		}
		
		$tpl->put('FORM', $this->form->display());
		
		return $this->generate_response($tpl);
	}
	
	private function init()
	{
		$this->lang = LangLoader::get('common', 'web');
		$this->common_lang = LangLoader::get('common');
	}
	
	private function build_form(HTTPRequestCustom $request)
	{
		$form = new HTMLForm(__CLASS__);
		
		$fieldset = new FormFieldsetHTML('web', $this->get_weblink()->get_id() === null ? $this->lang['web.add'] : $this->lang['web.edit']);
		$form->add_fieldset($fieldset);
		
		$fieldset->add_field(new FormFieldTextEditor('name', $this->common_lang['form.name'], $this->get_weblink()->get_name(), array('required' => true)));
		
		$search_category_children_options = new SearchCategoryChildrensOptions();
		$search_category_children_options->add_authorizations_bits(Category::READ_AUTHORIZATIONS);
		$search_category_children_options->add_authorizations_bits(Category::CONTRIBUTION_AUTHORIZATIONS);
		$fieldset->add_field(WebService::get_categories_manager()->get_select_categories_form_field('id_category', $this->common_lang['form.category'], $this->get_weblink()->get_id_category(), $search_category_children_options));
		
		$fieldset->add_field(new FormFieldUrlEditor('url', $this->common_lang['form.url'], $this->get_weblink()->get_url()->absolute(), array('required' => true)));
		
		$fieldset->add_field(new FormFieldRichTextEditor('contents', $this->common_lang['form.description'], $this->get_weblink()->get_contents(), array('rows' => 15, 'required' => true)));
		
		$fieldset->add_field(new FormFieldCheckbox('short_contents_enabled', $this->common_lang['form.short_contents.enabled'], $this->get_weblink()->is_short_contents_enabled(), 
			array('description' => StringVars::replace_vars($this->common_lang['form.short_contents.enabled.description'], array('number' => WebConfig::NUMBER_CARACTERS_BEFORE_CUT)), 'events' => array('click' => '
			if (HTMLForms.getField("short_contents_enabled").getValue()) {
				HTMLForms.getField("short_contents").enable();
			} else { 
				HTMLForms.getField("short_contents").disable();
			}'))
		));
		
		$fieldset->add_field(new FormFieldRichTextEditor('short_contents', $this->common_lang['form.short_contents'], $this->get_weblink()->get_short_contents(), array(
			'hidden' => !$this->get_weblink()->is_short_contents_enabled(),
		)));
		
		$other_fieldset = new FormFieldsetHTML('other', $this->common_lang['form.other']);
		$form->add_fieldset($other_fieldset);
		
		$other_fieldset->add_field(new FormFieldCheckbox('partner', $this->lang['web.form.partner'], $this->get_weblink()->is_partner(), array(
			'events' => array('click' => '
				if (HTMLForms.getField("partner").getValue()) {
					HTMLForms.getField("partner_picture").enable();
					HTMLForms.getField("privileged_partner").enable();
				} else {
					HTMLForms.getField("partner_picture").disable();
					HTMLForms.getField("privileged_partner").disable();
				}'
			)
		)));
		
		$other_fieldset->add_field(new FormFieldUploadPictureFile('partner_picture', $this->lang['web.form.partner_picture'], $this->get_weblink()->get_partner_picture()->relative(), array(
			'hidden' => !$this->get_weblink()->is_partner()
		)));
		
		$other_fieldset->add_field(new FormFieldCheckbox('privileged_partner', $this->lang['web.form.privileged_partner'], $this->get_weblink()->is_privileged_partner(), array(
			'description' => $this->lang['web.form.privileged_partner.explain'], 'hidden' => !$this->get_weblink()->is_partner()
		)));
		
		$other_fieldset->add_field(WebService::get_keywords_manager()->get_form_field($this->get_weblink()->get_id(), 'keywords', $this->common_lang['form.keywords'], array('description' => $this->common_lang['form.keywords.description'])));
		
		if (!$this->is_contributor_member())
		{
			$publication_fieldset = new FormFieldsetHTML('publication', $this->common_lang['form.approbation']);
			$form->add_fieldset($publication_fieldset);
			
			$publication_fieldset->add_field(new FormFieldDateTime('creation_date', $this->common_lang['form.date.creation'], $this->get_weblink()->get_creation_date(),
				array('required' => true)
			));
			
			$publication_fieldset->add_field(new FormFieldSimpleSelectChoice('approbation_type', $this->common_lang['form.approbation'], $this->get_weblink()->get_approbation_type(),
				array(
					new FormFieldSelectChoiceOption($this->common_lang['form.approbation.not'], WebLink::NOT_APPROVAL),
					new FormFieldSelectChoiceOption($this->common_lang['form.approbation.now'], WebLink::APPROVAL_NOW),
					new FormFieldSelectChoiceOption($this->common_lang['status.approved.date'], WebLink::APPROVAL_DATE),
				),
				array('events' => array('change' => '
				if (HTMLForms.getField("approbation_type").getValue() == 2) {
					jQuery("#' . __CLASS__ . '_start_date_field").show();
					HTMLForms.getField("end_date_enabled").enable();
				} else { 
					jQuery("#' . __CLASS__ . '_start_date_field").hide();
					HTMLForms.getField("end_date_enabled").disable();
				}'))
			));
			
			$publication_fieldset->add_field(new FormFieldDateTime('start_date', $this->common_lang['form.date.start'], ($this->get_weblink()->get_start_date() === null ? new Date() : $this->get_weblink()->get_start_date()), array('hidden' => ($this->get_weblink()->get_approbation_type() != WebLink::APPROVAL_DATE))));
			
			$publication_fieldset->add_field(new FormFieldCheckbox('end_date_enabled', $this->common_lang['form.date.end.enable'], $this->get_weblink()->is_end_date_enabled(), array(
			'hidden' => ($this->get_weblink()->get_approbation_type() != WebLink::APPROVAL_DATE),
			'events' => array('click' => '
			if (HTMLForms.getField("end_date_enabled").getValue()) {
				HTMLForms.getField("end_date").enable();
			} else { 
				HTMLForms.getField("end_date").disable();
			}'
			))));
			
			$publication_fieldset->add_field(new FormFieldDateTime('end_date', $this->common_lang['form.date.end'], ($this->get_weblink()->get_end_date() === null ? new Date() : $this->get_weblink()->get_end_date()), array('hidden' => !$this->get_weblink()->is_end_date_enabled())));
		}
		
		$this->build_contribution_fieldset($form);
		
		$fieldset->add_field(new FormFieldHidden('referrer', $request->get_url_referrer()));
		
		$this->submit_button = new FormButtonDefaultSubmit();
		$form->add_button($this->submit_button);
		$form->add_button(new FormButtonReset());
		
		$this->form = $form;
	}
	
	private function build_contribution_fieldset($form)
	{
		if ($this->get_weblink()->get_id() === null && $this->is_contributor_member())
		{
			$fieldset = new FormFieldsetHTML('contribution', LangLoader::get_message('contribution', 'user-common'));
			$fieldset->set_description(MessageHelper::display($this->lang['web.form.contribution.explain'] . ' ' . LangLoader::get_message('contribution.explain', 'user-common'), MessageHelper::WARNING)->render());
			$form->add_fieldset($fieldset);
			
			$fieldset->add_field(new FormFieldRichTextEditor('contribution_description', LangLoader::get_message('contribution.description', 'user-common'), '', array('description' => LangLoader::get_message('contribution.description.explain', 'user-common'))));
		}
	}
	
	private function is_contributor_member()
	{
		return (!WebAuthorizationsService::check_authorizations()->write() && WebAuthorizationsService::check_authorizations()->contribution());
	}
	
	private function get_weblink()
	{
		if ($this->weblink === null)
		{
			$id = AppContext::get_request()->get_getint('id', 0);
			if (!empty($id))
			{
				try {
					$this->weblink = WebService::get_weblink('WHERE web.id=:id', array('id' => $id));
				} catch (RowNotFoundException $e) {
					$error_controller = PHPBoostErrors::unexisting_page();
					DispatchManager::redirect($error_controller);
				}
			}
			else
			{
				$this->is_new_weblink = true;
				$this->weblink = new WebLink();
				$this->weblink->init_default_properties(AppContext::get_request()->get_getint('id_category', Category::ROOT_CATEGORY));
			}
		}
		return $this->weblink;
	}
	
	private function check_authorizations()
	{
		$weblink = $this->get_weblink();
		
		if ($weblink->get_id() === null)
		{
			if (!$weblink->is_authorized_to_add())
			{
				$error_controller = PHPBoostErrors::user_not_authorized();
				DispatchManager::redirect($error_controller);
			}
		}
		else
		{
			if (!$weblink->is_authorized_to_edit())
			{
				$error_controller = PHPBoostErrors::user_not_authorized();
				DispatchManager::redirect($error_controller);
			}
		}
		if (AppContext::get_current_user()->is_readonly())
		{
			$controller = PHPBoostErrors::user_in_read_only();
			DispatchManager::redirect($controller);
		}
	}
	
	private function save()
	{
		$weblink = $this->get_weblink();
		$previous_category_id = $weblink->get_id_category();
		
		$weblink->set_name($this->form->get_value('name'));
		$weblink->set_rewrited_name(Url::encode_rewrite($weblink->get_name()));
		$weblink->set_id_category($this->form->get_value('id_category')->get_raw_value());
		$weblink->set_url(new Url($this->form->get_value('url')));
		$weblink->set_contents($this->form->get_value('contents'));
		$weblink->set_short_contents(($this->form->get_value('short_contents_enabled') ? $this->form->get_value('short_contents') : ''));
		$weblink->set_partner($this->form->get_value('partner'));
		$weblink->set_partner_picture(new Url($this->form->get_value('partner_picture')));
		$weblink->set_privileged_partner($this->form->get_value('privileged_partner'));
		
		if ($this->is_contributor_member())
		{
			if ($weblink->get_id() === null )
				$weblink->set_creation_date(new Date());
			
			$weblink->set_approbation_type(WebLink::NOT_APPROVAL);
			$weblink->clean_start_and_end_date();
		}
		else
		{
			$weblink->set_creation_date($this->form->get_value('creation_date'));
			$weblink->set_approbation_type($this->form->get_value('approbation_type')->get_raw_value());
			if ($weblink->get_approbation_type() == WebLink::APPROVAL_DATE)
			{
				$deferred_operations = $this->config->get_deferred_operations();
				
				$old_start_date = $weblink->get_start_date();
				$start_date = $this->form->get_value('start_date');
				$weblink->set_start_date($start_date);
				
				if ($old_start_date !== null && $old_start_date->get_timestamp() != $start_date->get_timestamp() && in_array($old_start_date->get_timestamp(), $deferred_operations))
				{
					$key = array_search($old_start_date->get_timestamp(), $deferred_operations);
					unset($deferred_operations[$key]);
				}
				
				if (!in_array($start_date->get_timestamp(), $deferred_operations))
					$deferred_operations[] = $start_date->get_timestamp();
				
				if ($this->form->get_value('end_date_enabled'))
				{
					$old_end_date = $weblink->get_end_date();
					$end_date = $this->form->get_value('end_date');
					$weblink->set_end_date($end_date);
					
					if ($old_end_date !== null && $old_end_date->get_timestamp() != $end_date->get_timestamp() && in_array($old_end_date->get_timestamp(), $deferred_operations))
					{
						$key = array_search($old_end_date->get_timestamp(), $deferred_operations);
						unset($deferred_operations[$key]);
					}
					
					if (!in_array($end_date->get_timestamp(), $deferred_operations))
						$deferred_operations[] = $end_date->get_timestamp();
				}
				else
				{
					$weblink->clean_end_date();
				}
				
				$this->config->set_deferred_operations($deferred_operations);
				WebConfig::save();
			}
			else
			{
				$weblink->clean_start_and_end_date();
			}
		}
		
		if ($weblink->get_id() === null)
		{
			$id = WebService::add($weblink);
		}
		else
		{
			$id = $weblink->get_id();
			WebService::update($weblink);
		}
		
		$this->contribution_actions($weblink, $id);
		
		WebService::get_keywords_manager()->put_relations($id, $this->form->get_value('keywords'));
		
		Feed::clear_cache('web');
		WebCache::invalidate();
		
		if ($previous_category_id != $weblink->get_id_category())
			WebCategoriesCache::invalidate();
	}
	
	private function contribution_actions(WebLink $weblink, $id)
	{
		if ($weblink->get_id() === null)
		{
			if ($this->is_contributor_member())
			{
				$contribution = new Contribution();
				$contribution->set_id_in_module($id);
				$contribution->set_description(stripslashes($this->form->get_value('contribution_description')));
				$contribution->set_entitled($weblink->get_name());
				$contribution->set_fixing_url(WebUrlBuilder::edit($id)->relative());
				$contribution->set_poster_id(AppContext::get_current_user()->get_id());
				$contribution->set_module('web');
				$contribution->set_auth(
					Authorizations::capture_and_shift_bit_auth(
						WebService::get_categories_manager()->get_heritated_authorizations($weblink->get_id_category(), Category::MODERATION_AUTHORIZATIONS, Authorizations::AUTH_CHILD_PRIORITY),
						Category::MODERATION_AUTHORIZATIONS, Contribution::CONTRIBUTION_AUTH_BIT
					)
				);
				ContributionService::save_contribution($contribution);
			}
		}
		else
		{
			$corresponding_contributions = ContributionService::find_by_criteria('web', $id);
			if (count($corresponding_contributions) > 0)
			{
				$weblink_contribution = $corresponding_contributions[0];
				$weblink_contribution->set_status(Event::EVENT_STATUS_PROCESSED);
				
				ContributionService::save_contribution($weblink_contribution);
			}
		}
		$weblink->set_id($id);
	}
	
	private function redirect()
	{
		$weblink = $this->get_weblink();
		$category = $weblink->get_category();
		
		if ($this->is_new_weblink && $this->is_contributor_member() && !$weblink->is_visible())
		{
			DispatchManager::redirect(new UserContributionSuccessController());
		}
		elseif ($weblink->is_visible())
		{
			if ($this->is_new_weblink)
				AppContext::get_response()->redirect(WebUrlBuilder::display($category->get_id(), $category->get_rewrited_name(), $weblink->get_id(), $weblink->get_rewrited_name()), StringVars::replace_vars($this->lang['web.message.success.add'], array('name' => $weblink->get_name())));
			else
				AppContext::get_response()->redirect(($this->form->get_value('referrer') ? $this->form->get_value('referrer') : WebUrlBuilder::display($category->get_id(), $category->get_rewrited_name(), $weblink->get_id(), $weblink->get_rewrited_name())), StringVars::replace_vars($this->lang['web.message.success.edit'], array('name' => $weblink->get_name())));
		}
		else
		{
			if ($this->is_new_weblink)
				AppContext::get_response()->redirect(WebUrlBuilder::display_pending(), StringVars::replace_vars($this->lang['web.message.success.add'], array('name' => $weblink->get_name())));
			else
				AppContext::get_response()->redirect(($this->form->get_value('referrer') ? $this->form->get_value('referrer') : WebUrlBuilder::display_pending()), StringVars::replace_vars($this->lang['web.message.success.edit'], array('name' => $weblink->get_name())));
		}
	}
	
	private function generate_response(View $tpl)
	{
		$weblink = $this->get_weblink();
		
		$response = new SiteDisplayResponse($tpl);
		$graphical_environment = $response->get_graphical_environment();
		
		$breadcrumb = $graphical_environment->get_breadcrumb();
		$breadcrumb->add($this->lang['module_title'], WebUrlBuilder::home());
		
		if ($weblink->get_id() === null)
		{
			$graphical_environment->set_page_title($this->lang['web.add']);
			$breadcrumb->add($this->lang['web.add'], WebUrlBuilder::add($weblink->get_id_category()));
			$graphical_environment->get_seo_meta_data()->set_description($this->lang['web.add'], $this->lang['module_title']);
			$graphical_environment->get_seo_meta_data()->set_canonical_url(WebUrlBuilder::add($weblink->get_id_category()));
		}
		else
		{
			$graphical_environment->set_page_title($this->lang['web.edit']);
			$graphical_environment->get_seo_meta_data()->set_description($this->lang['web.edit'], $this->lang['module_title']);
			$graphical_environment->get_seo_meta_data()->set_canonical_url(WebUrlBuilder::edit($weblink->get_id()));
			
			$categories = array_reverse(WebService::get_categories_manager()->get_parents($weblink->get_id_category(), true));
			foreach ($categories as $id => $category)
			{
				if ($category->get_id() != Category::ROOT_CATEGORY)
					$breadcrumb->add($category->get_name(), WebUrlBuilder::display_category($category->get_id(), $category->get_rewrited_name()));
			}
			$category = $weblink->get_category();
			$breadcrumb->add($weblink->get_name(), WebUrlBuilder::display($category->get_id(), $category->get_rewrited_name(), $weblink->get_id(), $weblink->get_rewrited_name()));
			$breadcrumb->add($this->lang['web.edit'], WebUrlBuilder::edit($weblink->get_id()));
		}
		
		return $response;
	}
}
?>
