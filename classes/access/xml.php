<?php
// --------------------------------------------
// | The EP-Dev Forum News script        
// |                                           
// | Copyright (c) 2002-2006 EP-Dev.com :           
// | This program is distributed as free       
// | software under the GNU General Public     
// | License as published by the Free Software 
// | Foundation. You may freely redistribute     
// | and/or modify this program.               
// |                                           
// --------------------------------------------

/* ------------------------------------------------------------------ */
//	XML Access Class
//
//	Controls access to remote XML files. By doing this the script
//	can easily access multiple XML files.
/* ------------------------------------------------------------------ */


class EP_Dev_Forum_News_XML
{
    var $handle;
    var $parser;
    var $source;

    var $root;
    var $lastChild;


	function EP_Dev_Forum_News_XML($username, $password, $host, $name, $prefix, &$error_handle)
	{

		$this->ERROR =& $error_handle;

		$this->root = null;

		$this->lastChild = null;

		$this->source = $host;

	}


    function load()
    {
        $this->handle = fopen($this->source, "r")
            or die("Error reading XML file: {$this->source}");
        
        // create parser
        $this->parser = xml_parser_create();

        // set to this object
        xml_set_object($this->parser, $this);

        // set method handlers
        xml_set_element_handler($this->parser, "xmlStartElement", "xmlEndElement");

        // set data handler
        xml_set_character_data_handler($this->parser, "xmlElementData");
    }


    function parse()
    {
        while ($data = fread($this->handle, 4096))
        {
            xml_parse($this->parser, $data, feof($this->handle))
                or die(
                        sprintf(
                            "XML error ({$this->source}): %s at line %d", 
                            xml_error_string( xml_get_error_code(  $this->parser  ) ), 
                            xml_get_current_line_number($this->parser)
                        )
                      ); 
        }

        fclose($this->handle);
        xml_parser_free($this->parser);
    }


    function &getRoot()
    {
        return $this->root;
    }


    function &getLastOpenChild()
    {
        $lastChild =& $this->getLastChild();

        if ($lastChild == null)
        {
            return null;
        }
        else
        {
            while (!$lastChild->isOpen())
            {
                $lastChild =& $lastChild->getParent();

                if ($lastChild->isOpen() && $lastChild->getNumberChildren() > 0)
                {
                    for($i=0; $i<$lastChild->getNumberChildren(); $i++)
                    {
						$current_child =& $lastChild->getChild($i+1);
                        if ($current_child->isOpen())
                        {
                            $lastChild =& $lastChild->getChild($i+1);
                            $i=0;
                        }
                    }
                }
            }
        }

        return $lastChild;
    }


    function &getLastChild()
    {
        return $this->lastChild;
    }


    function setLastChild(&$child)
    {
        $this->lastChild =& $child;
    }


    function xmlStartElement($parser, $tagName, $attributes)
    {
        if ($this->root == null)
        {
            $this->root = new EP_Dev_Forum_News_XML_Tag($tagName, $attributes, $this->root);
            $this->setLastChild($this->root);
        }
        else
        {
            $lastOpenChild =& $this->getLastOpenChild();
            $lastOpenChild->addChild( new EP_Dev_Forum_News_XML_Tag($tagName, $attributes, $lastOpenChild) );
            $this->setLastChild( $lastOpenChild->getChild( $lastOpenChild->getNumberChildren() )  );
        }
    }

    function xmlEndElement($parser, $tagName)
    {
		$lastChild =& $this->getLastOpenChild();
        $lastChild->close();
    }

    function xmlElementData($parser, $data)
    {
		$lastChild =& $this->getLastOpenChild();
        $lastChild->addData($data);
    }
}


class EP_Dev_Forum_News_XML_Tag
{
    var $name;
    var $attributes;
    var $data;

    var $status;
    var $children;
    var $parent;

    var $childrenNames;


    function EP_Dev_Forum_News_XML_Tag($name, $attributes, &$parent)
    {
        $this->name = $name;
        $this->attributes = $attributes;
        $this->parent =& $parent;
        $this->setData("");
        $this->setOpen(true);
    }


    function isOpen()
    {
        return $this->status;
    }


    function open()
    {
        $this->setOpen(true);
    }


    function close()
    {
        $this->setOpen(false);
    }


    function addChild(&$cLink)
    {
        $this->children[] =& $cLink;

        $this->childrenNames[$cLink->getName()] = $this->getNumberChildren();

        // add blank data to sync children & data
        $this->addData("");
    }


    function addData($data)
    {
        // always base data off of current children number
        $this->data[$this->getNumberChildren()] .= $data;
    }


    function setData($data)
    {
        unset($this->data);
        $this->data[0] = $data;
    }


    function setOpen($openStatus)
    {
        $this->status = $openStatus;
    }


    function getAttributes()
    {
        return $this->attributes;
    }


    function getAttribute($attributeName)
    {
        return $this->attributes[$attributeName];
    }


    function getAttributesString()
    {
        $attrString = "";

        if (!empty($this->attributes))
        {
            foreach($this->attributes as $attribute => $value)
            {
                $attrString .= " {$attribute}=\"{$value}\"";
            }
        }

        return $attrString;
    }


    function &getChildren()
    {
        return $this->children;
    }


    function &getChild($child)
    {
        return $this->children[$child-1];
    }


    function &getChildByName($child)
    {
        return $this->getChild($this->childrenNames[$child]);
    }


    function getData($part=0)
    {
        if ($part != 0)
        {
            return $this->data[$part-1];
        }
        else
        {
            $all_data = "";

            foreach($this->data as $data)
            {
                $all_data .= $data;
            }

            return $all_data;
        }
    }


    function getName()
    {
        return $this->name;
    }


    function getNumberAttributes()
    {
        return count($this->attributes);
    }


    function getNumberChildren()
    {
        return count($this->children);
    }


    function getNumberData()
    {
        return count($this->data);
    }


    function &getParent()
    {
        return $this->parent;
    }
} 
