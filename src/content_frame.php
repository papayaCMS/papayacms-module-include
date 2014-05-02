<?php
/**
* Iframe page, can
*
* @copyright 2013 by papaya Software GmbH - All rights reserved.
* @link http://www.papaya-cms.com/
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
*
* You can redistribute and/or modify this script under the terms of the GNU General Public
* License (GPL) version 2, provided that the copyright and license notes, including these
* lines, remain unmodified. papaya is distributed in the hope that it will be useful, but
* WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
* FOR A PARTICULAR PURPOSE.
*
* @package Papaya-Modules
* @subpackage Free-Include
* @version $Id: content_frame.php 39797 2014-05-06 15:55:49Z weinert $
*/

/**
* IFrame embed
*
* @package Papaya-Modules
* @subpackage Free-Include
*/
class content_frame
  extends
    PapayaObject
  implements
    PapayaPluginAppendable,
    PapayaPluginQuoteable,
    PapayaPluginEditable,
    PapayaPluginCacheable {

  /**
   * @var PapayaPluginEditableContent
   */
  private $_content = NULL;
  /**
   * @var PapayaUiReferenceFactory
   */
  private $_references = NULL;

  /**
   * @var PapayaCacheIdentifierDefinition;
   */
  private $_cacheDefinition = NULL;

  /**
   * Append the page output xml to the DOM.
   *
   * @see PapayaXmlAppendable::appendTo()
   */
  public function appendTo(PapayaXmlElement $parent) {
    $parent->appendElement('title', array(), $this->content()->get('title', ''));
    $parent->appendElement('teaser')->appendXml($this->content()->get('teaser', ''));
    $parent->appendElement('text')->appendXml($this->content()->get('text', ''));

    $reference = $this->references()->byString($this->content()->get('url', ''));
    $reference->setParameters(
      $this->papaya()->request->getParameters(PapayaRequest::SOURCE_QUERY)
    );

    $parent->appendElement('url', array(), $reference->getRelative());
    $parent->appendElement('height', array(), $this->content()->get('height', 600));

    if ($this->content()->get('width', 0) > 0) {
      $parent->appendElement('width', array(), $this->content()->get('width'));
    }
    $parent->appendElement('scrollbars', array(), $this->content()->get('scrollbars', 'auto'));
  }

  /**
   * Append the teaser output xml to the DOM.
   *
   * @see PapayaXmlAppendable::appendTo()
   */
  public function appendQuoteTo(PapayaXmlElement $parent) {
    $parent->appendElement('title', array(), $this->content()->get('title', ''));
    $parent->appendElement('text')->appendXml($this->content()->get('teaser', ''));
  }

  /**
   * The content is an {@see ArrayObject} containing the stored data.
   *
   * @see PapayaPluginEditable::content()
   * @param PapayaPluginEditableContent $content
   * @return \PapayaPluginEditableContent
   */
  public function content(PapayaPluginEditableContent $content = NULL) {
    if (isset($content)) {
      $this->_content = $content;
    } elseif (NULL === $this->_content) {
      $this->_content = new PapayaPluginEditableContent();
      $this->_content->callbacks()->onCreateEditor = array($this, 'createEditor');
    }
    return $this->_content;
  }

  /**
   * Define the code definition parameters for the output.
   *
   * @see PapayaPluginCacheable::cacheable()
   * @param PapayaCacheIdentifierDefinition $definition
   * @return PapayaCacheIdentifierDefinition
   */
  public function cacheable(PapayaCacheIdentifierDefinition $definition = NULL) {
    if (isset($definition)) {
      $this->_cacheDefinition = $definition;
    } elseif (NULL == $this->_cacheDefinition) {
      $this->_cacheDefinition = new PapayaCacheIdentifierDefinitionUrl();
    }
    return $this->_cacheDefinition;
  }

  /**
   * The editor is used to change the stored data in the administration interface.
   *
   * In this case it the editor creates an dialog from a field definition.
   *
   * @see PapayaPluginEditableContent::editor()
   *
   * @param object $callbackContext
   * @param PapayaPluginEditableContent $content
   * @return PapayaPluginEditor
   */
  public function createEditor($callbackContext, PapayaPluginEditableContent $content) {
    $editor = new PapayaAdministrationPluginEditorFields(
      $content,
      array(
        'IFrame',
        'url' => array(
          'caption' => 'URL',
          'filter' => new PapayaFilterNotEmpty(),
          'mandatory' => TRUE,
          'type' => 'input_page',
          'default' => '',
          'hint' => 'Please input a relative or an absolute URL.'
        ),
        'pass_parameters' => array(
          'caption' => 'Pass parameters',
          'filter' => new PapayaFilterBooleanString(),
          'mandatory' => TRUE,
          'type' => 'yesno',
          'parameters' => 800,
          'hint' => 'Pass parameters from the outer page into the iframe',
          'default' => FALSE
        ),
        'width' => array(
          'caption' => 'Width',
          'filter' => new PapayaFilterInteger(),
          'type' => 'input',
          'parameters' => 800,
          'hint' => 'Iframe width, width will be used in the template. (optional)',
          'default' => 0
        ),
        'height' => array(
          'caption' => 'Height',
          'filter' => new PapayaFilterInteger(),
          'type' => 'input',
          'parameters' => 800,
          'hint' => 'Iframe height, width will be used in the template.',
          'default' => 600
        ),
        'scrollbars' => array(
          'caption' => 'Scrollbars',
          'filter' => new PapayaFilterInteger(),
          'type' => 'select',
          'parameters' => array('auto' => 'Auto', 'no' => 'No', 'yes' => 'Yes'),
          'default' => 'auto'
        ),
        'Content',
        'title' => array(
          'caption' => new PapayaUiStringTranslated('Title'),
          'mandatory' => TRUE,
          'type' => 'input',
          'parameters' => 400
        ),
        'teaser' => array(
          'caption' => new PapayaUiStringTranslated('Teaser'),
          'type' => 'richtext_simple',
          'parameters' => 6
        ),
        'text' => array(
          'caption' => new PapayaUiStringTranslated('Text'),
          'type' => 'richtext',
          'parameters' => 20
        )
      )
    );
    $editor->papaya($this->papaya());
    return $editor;
  }

  /**
   * The references is an factory to create specific Reference objects from a string
   *
   * @param PapayaUiReferenceFactory $references
   * @return \PapayaUiReferenceFactory
   */
  public function references(PapayaUiReferenceFactory $references = NULL) {
    if (isset($references)) {
      $this->_references = $references;
    } elseif (NULL === $this->_references) {
      $this->_references = new PapayaUiReferenceFactory();
      $this->_references->papaya($this->papaya());
    }
    return $this->_references;
  }
}


