<?php

namespace RedCat\CSVTools;
use UnexpectedValueException;

/**
 * Delimiter Finder
 *
 * Attempt to ascertain the delimiter used in a
 * character separated data file from a defined 
 * list of likely delimiters.
 *
 * @author Darragh Enright <darraghenright@gmail.com>
 */ 
class DelimiterFinder
{
    /**
     * @var string $delimiters
     */
    //private $delimiters = array('\t', ',', ';');
    private $delimitersRE = '~[,;]~';

    /**
     * @var string $file
     */
    private $file;

    /**
     * @var mixed $match
     */
    private $match = false;
    
    /**
     * Constructor
     *
     * @param string $file Path to the file to read
     */
    public function __construct($file)
    {
        $this->validateFile($file);
        $this->file = $file;
    }
    
    /**
     * Return an array of registered delimiters
     *
     * @return array
     */
    public function getDelimiters()
    {
        return $this->delimiters;
    }
    
    /**
     * Add a new delimiter to the array 
     * of registered delimiters
     *
     * @param  string
     * @throws UnexpectedValueException
     */
    public function addDelimiter($delimiter)
    {
        $this->validateDelimiter($delimiter);  
        
        if (!in_array($delimiter, $this->delimiters)) {
            $this->delimiters[] = $delimiter;
        }
    }
    
    /**
     * Perform the delimiter search if a match 
     * has not been made and return the result
     *
     * @return mixed
     */
    public function find()
    {
        if (false === $this->match) {
            $this->search();
        } 
        
        return $this->match;
    }
    
    /**
     * Validate the file. Throw an exception if:
     *
     * * The file does not exist
     * * The file is not readable
     *
     * @param  string $file
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function validateFile($file)
    {
        if (!is_file($file)) {
            throw new InvalidArgumentException(
                sprintf('The file "%s" does not exist', $file)
            );
        }
        
        if (!is_readable($file)) {
            throw new RuntimeException(
                sprintf('The file "%s" is not readable', $file)
            );
        }
    }

    /**
     * Validate the file. Throw an exception if
     * the string is not exactly one character
     *
     * @param  string $delimiter
     * @throws UnexpectedValueException
     */    
    protected function validateDelimiter($delimiter)
    {
        if (1 !== strlen($delimiter)) {
            throw new UnexpectedValueException(
                sprintf('The delimiter "%s" is not a single character', $delimiter)
            );
        }
    }
    
    /**
     * Determine a likely delimiter.
     *
     * Add doc and refactor this code
     */   
     /*
    protected function search()
    {
        $handle = fopen($this->file, 'r');
        
        $regex = sprintf('/[^%s]/', implode($this->delimiters));
        $lines = array();
        $loops = 0;
        
        while (!feof($handle)) {
        
            $line  = fgets($handle);
            $chars = preg_replace($regex, null, $line);
            $count = count_chars($chars, 1);
        
            $lines[] = $count;
            
            if ($loops++ > 1) {
                
                $matched = call_user_func_array('array_intersect_assoc', $lines);
            
                if (1 === count($matched)) {
                    $this->match = chr(key($matched));
                    break;
                }
            }
        }
        
        fclose($handle);
    }
    */
    
    protected function search(){
		$handle = fopen($this->file, 'r');
		$delimitersByLine = [];
		while (!feof($handle)) {
		//foreach ($this->fileContents as $lineNumber => $line){
			$line  = fgets($handle);
			$quoted = false;
			$delimiters = array();

			for ($i = 0; $i < strlen($line) - 1; $i++){
				$char = substr($line, $i, 1);
				if ($char === '"'){
					$quoted = !$quoted;
				}
				else if (!$quoted && preg_match($this->delimitersRE, $char)){
					if (array_key_exists($char, $delimiters)){
						$delimiters[$char]++;
					}
					else{
						$delimiters[$char] = 1;
					}
				}
			}

			if (empty($delimitersByLine)){
				$delimitersByLine = $delimiters;
			}
			else{
				$newDelimitersByLine = $delimiters;
				foreach ($delimitersByLine as $key => $value){
					if ((array_key_exists($key, $delimiters) && $delimiters[$key] === $value) || !array_key_exists($key, $delimiters)){
						$newDelimitersByLine[$key] = $value;
					}
				}
				$delimitersByLine = $newDelimitersByLine;
				if (sizeof($delimitersByLine) < 2){
					break;
				}
			}
		}

		arsort($delimitersByLine);
		$firstDelimiter = key($delimitersByLine);

		if (sizeof($delimitersByLine) > 1){
			next($delimitersByLine);
			$nextDelimiter = key($delimitersByLine);
			if ($delimitersByLine[$firstDelimiter] === $delimitersByLine[$nextDelimiter]){
				// multiple delimiters with the same frequency found
				// throw an error
				throw new UnexpectedValueException();
			}
			$this->match = $firstDelimiter;
		}
		else{
			$this->match = $firstDelimiter;
		}
		
		fclose($handle);
	}
   
}