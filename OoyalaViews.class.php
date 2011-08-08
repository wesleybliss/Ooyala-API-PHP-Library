<?php
    
    /*
     * Ooyala API PHP Class Library Extension
     * (OoyalaViews.class.php)
     *
     * Adds functionality to the Ooyala class
     * providing the ability to output more well-formed
     * content, particularly focusing on (X)HTML
     *
     * @created 2010
     * @author Wesley Bliss | http://wesleybliss.com/
     *
     * @todo General code cleanup; convert comment style to PHPDoc syntax
     *
     */
    
    
    class OoyalaViews extends Ooyala {
        
        //
        // same construct as Ooyala class
        //
        function __construct( $partnerCode, $secretCode ) {
            $this->partnerCode = $partnerCode;
            $this->secretCode = $secretCode;
        }
        
        //
        // takes a flat list (array) of labels and if they have
        // children, formats them into a multidimensional array
        //
        public function nestLabels( $labels ) {
            
            foreach ( $labels as $label => $videoCount ) {
                
                // remove root/leading forward slash
                if ( substr($label, 0, 1) == '/' ) {
                    $label = substr( $label, 1 );
                }
                
                // check if label has children
                if ( strpos($label, '/') <= 0 ) {
                    
                    // label has no children, so just add it w/video count
                    $nestedLabels[$label] = $videoCount;
                    
                }
                else {
                    
                    // label has children, so create a multidimensional array
                    
                    $children = explode( '/', $label );
                    
                    $childKeys = array_keys( $children );
                    
                    $ubound = max( $childKeys );
                    
                    $parent = $children[0];
                    unset( $children[0], $childKeys[0] );
                    
                    $lbound = min( $childKeys );
                    
                    if ( count($children) == 1 ) {
                        
                        // if there's only one child, no need to loop
                        $children[ $children[$lbound] ] = $videoCount;
                        
                    }
                    else {
                        
                        // keep track of the most recent parent key
                        $lastParent = $children[ $ubound - 1 ];
                        
                        // set the last child's value to the number of videos
                        // the label contains, then add that to the 2nd-to-last
                        // label as a child array
                        $children[ $lastParent ] = array(
                            $children[$ubound] => $videoCount
                        );
                        
                        // remove the last label from the original array
                        unset( $children[$ubound] );
                        
                        // update array bounds
                        $ubound--;
                        
                        // loop through the remaining labels from last to first,
                        // adding the last child (now an array) to the 2nd-to-last
                        // child, with the 2nd-to-last child's label as the array key
                        while ( $ubound > $lbound ) {
                            
                            // 2nd-to-last child label => last child label (now a sub-array)
                            // which will create a nested/multidimensional array like this:
                            //      ORIGINAL:
                            //          $children = array(
                            //              [1] => first,
                            //              [2] => second,
                            //              [3] => third
                            //              [4] => last
                            //          );
                            //      MODIFIED:
                            //          $children = array(
                            //              [1] => first,
                            //              [2] => second,
                            //              [third] => array(
                            //                  [last] => $videoCount
                            //              )
                            //          );
                            $children[ $children[$ubound - 1] ] = array(
                                $lastParent => $children[$lastParent]
                            );
                            
                            // remove the last child's KEY from the array, and remove
                            // the actual last child, which is now set as the value
                            // of [last - 1]'s key
                            unset( $children[$ubound], $children[$lastParent] );
                            
                            // keep track of the most recent parent we used
                            $lastParent = $children[$ubound - 1];
                            
                            // climb up the array
                            $ubound--;
                            
                        }
                        
                    }
                    
                    // remove the last remaining item from the original array
                    unset( $children[$ubound] );
                    
                    // add the nested/multidimensional children array to the main
                    // array, with the top-most label (now the parent of those children) as it's key
                    $nestedLabels[$parent] = $children;
                    
                }
                
            }
            
            return $nestedLabels;
            
        } // function nestLabels
        
        
        //
        // gets a list of labels from Backlot, but instead of
        // returning a flat array of labels with slash-delimited
        // children, format them into a multidimensional array
        //
        // example:
        //      (assume $videoCount == 23)
        //      (original) $labels[$i] = '/parent/child1/child2'
        //      (becomes)  $labels[$parent] = array(
        //                      'child1' => array(
        //                          'child2' => $videoCount
        //                      )
        //                 );
        //
        public function queryLabelsListNested( $params = array() ) {
            
            // expanded statement, just for simplicity
            // $labels = $this->queryLabelsList( $params );
            // return $this->nestLabels( $labels );
            
            return $this->nestLabels(
                $this->queryLabelsList( $params )
            );
            
        } // function queryLabelsListNested
        
        
        public function nestedLabelsToHTMLCheckboxes( $nestedLabels, $tree = '', $indent = 2 ) {
            
            //
            // TODO: maybe switch \t tabs to spaces ('    ' [4?])
            //
            
            $tabs = str_repeat( "\t", $indent );
            $html = PHP_EOL . $tabs . '<ul>';
            $indent++;
            
            foreach ( $nestedLabels as $label => $children ) {
                
                $html .= PHP_EOL . str_repeat( "\t", $indent ) . '<li>';
                
                $indent++;
                
                if ( !is_array( $children ) ) {
                    $html .= PHP_EOL . str_repeat("\t", $indent) . '<label>' . PHP_EOL .
                        str_repeat("\t", ($indent + 1)) .
                        '<input type="checkbox" name="labels[]" value="' .
                        ($tree . '/' . $label) . '" />' . PHP_EOL . str_repeat("\t", ($indent + 1)) .
                        $label . PHP_EOL . str_repeat("\t", $indent) .
                        '</label>' . PHP_EOL . str_repeat("\t", ($indent - 1));
                    $indent--;
                }
                else {
                    $html .= PHP_EOL . str_repeat( "\t", $indent ) .
                        '<label>' . PHP_EOL . str_repeat("\t", ($indent + 1)) .
                        '<input type="checkbox" name="labels[]" value="' .
                        ($tree . '/' . $label) . '" />' . PHP_EOL . str_repeat("\t", ($indent + 1)) .
                        $label . PHP_EOL . str_repeat("\t", $indent) .
                        '</label>';
                    $html .= $this->nestedLabelsToHTMLCheckboxes( $children, ($tree . '/' . $label), $indent );
                    $indent--;
                    //$html .= PHP_EOL . str_repeat( "\t", $indent );
                    $html .=  str_repeat("\t", $indent);
                }
                
                $html .= '</li>';
                
            }
            
            return $html . PHP_EOL . $tabs . '</ul>' . PHP_EOL;
            
        }
        
        
    } // OoyalaViews
    
?>
