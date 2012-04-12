<?php
/*##################################################
 *                              AbstractCommentsBuildForm.class.php
 *                            -------------------
 *   begin                : September 25, 2011
 *   copyright            : (C) 2011 Kevin MASSY
 *   email                : soldier.weasel@gmail.com
 *
 *
 ###################################################
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 ###################################################*/

 /**
 * @author Kevin MASSY <soldier.weasel@gmail.com>
 * @package {@package}
 */
abstract class AbstractCommentsBuildForm
{
	private $form;
	private $submit_button;
	private $message_response;
	
	public function display()
	{
		return $this->form->display();
	}
	
	protected abstract function create_form();
	
	protected abstract function handle_submit();
	
	protected function has_been_submited()
	{
		return $this->submit_button->has_been_submited() && $this->form->validate();
	}
	
	protected function get_form()
	{
		return $this->form;
	}
	
	protected function set_form(HTMLForm $form)
	{
		$this->form = $form;
	}

	protected function set_submit_button(FormButtonSubmit $submit_button)
	{
		$this->submit_button = $submit_button;
	}
	
	protected function set_message_response($message_helper)
	{
		$this->message_response = $message_helper;
	}
	
	public function get_message_response()
	{
		return $this->message_response;
	}
}
?>