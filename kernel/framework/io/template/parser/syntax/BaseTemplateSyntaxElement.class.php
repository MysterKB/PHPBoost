<?php
/*##################################################
 *                    BaseTemplateSyntaxElement.class.php
 *                            -------------------
 *   begin                : July 10 2010
 *   copyright            : (C) 2010 Loic Rouchon
 *   email                : horn@phpboost.com
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

class BaseTemplateSyntaxElement extends AbstractTemplateSyntaxElement
{
	public function parse(TemplateSyntaxParserContext $context, StringInputStream $input, StringOutputStream $output)
	{
        $this->register($context, $input, $output);
		$this->do_parse();
	}

	private function do_parse()
	{
		while ($this->input->has_next())
		{
			$element = null;
			$current = $this->input->next();
		    if ($current == '{' && $this->input->assert_next('(?:@(?:H\|)?)?(?:\w+\.)*\w+\}'))
            {
                $element = new VariableExpressionTemplateSyntaxElement();
            }
			elseif ($current == '$' && $this->input->assert_next('\{'))
			{
				$element = $this->build_expression_elt();
			}
            elseif ($current == '#' && $this->input->assert_next('\{'))
            {
                $element = new FunctionCallTemplateSyntaxElement();
            }
            elseif ($current == '#' && $this->input->assert_next('[\s]'))
            {
                $element = $this->build_statement_elt();
                if ($element === null)
                {   // every other statement if processed at a higher level
                    return;
                }
            }
		    elseif ($current == '<' && $this->input->assert_next('\?php'))
            {
                $element = new PHPTemplateSyntaxElement();
            }
			else
			{
				$element = $this->build_text_elt();
			}
			$this->parse_elt($element);
		}
	}

	private function build_expression_elt()
	{
		return new ExpressionTemplateSyntaxElement();
	}

	private function build_text_elt()
	{
		$this->input->move(-1);
		return new TextTemplateSyntaxElement();
	}

	private function build_statement_elt()
	{
		$this->input->move(-1);
		if (ConditionTemplateSyntaxElement::is_element($this->input))
		{
			return new ConditionTemplateSyntaxElement();
		}
        elseif (LoopTemplateSyntaxElement::is_element($this->input))
        {
            return new LoopTemplateSyntaxElement();
        }
        elseif (IncludeTemplateSyntaxElement::is_element($this->input))
        {
            return new IncludeTemplateSyntaxElement();
        }
		return null;
	}
}
?>