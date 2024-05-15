<?php
/**
 * Copyright 2017 Lengow SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Lengow
 * @package     Lengow_Connector
 * @subpackage  Helper
 * @author      Team module <team-module@lengow.com>
 * @copyright   2017 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


namespace Lengow\Connector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * NameParser class
 */
class NameParser extends AbstractHelper
{

    /**
     * Array of possible name languages.
     * @var array
     */
    private array $languages;

    /**
     * Array of possible name titles.
     * @var array
     */
    private array $titles;

    /**
     * Array of possible last name prefixes.
     * @var array
     */
    private array $prefices;

    /**
     * Array of possible name suffices.
     * @var array;
     */
    private array $suffices;

    /**
     * The TITLE ie. Dr., Mr. Mrs., etc...
     * @var string
     */
    private string $title;

    /**
     * The FIRST Name
     * @var string
     */
    private string $first;

    /**
     * The MIDDLE Name
     * @var string
     */
    private string $middle;

    /**
     * The LAST Name
     * @var string
     */
    private string $last;

    /**
     * Name addendum ie. III, Sr., etc...
     * @var string
     */
    private string $suffix;

    /**
     * Full name string passed to class
     * @var string
     */
    private string $fullName;

    /**
     * @bool
     */
    private bool $notParseable;

    /**
     *
     * @param Context $context
     * @param string $initString
     */
    public function __construct(Context $context, string $initString = "")
    {
        parent::__construct($context);
        $this->title = "";
        $this->first = "";
        $this->middle = "";
        $this->last = "";
        $this->suffix = "";
        $this->notParseable = false;


        $paramsJson = $this->getJsonParserConfig();
        $params = json_decode($paramsJson, true);

        // added Military Titles
        $this->languages = $params["language"];
        $this->titles = $params["titles"];

        $this->prefices = $params["prefices"];
        $this->suffices = $params["suffices"];
        $this->fullName = "";
        $this->notParseable = false;

        // if initialized by value, set class variable and then parse
        if ($initString != "") {
            $this->fullName = $initString;
            $this->parse();
        }
    }

    /**
     * Access Method
     *
     */
    public function getFirstName(): string
    {
        return $this->first;
    }

    /**
     * Access Method
     *
     */
    public function getMiddleName(): string
    {
        return $this->middle;
    }

    /**
     * Access Method
     *
     */
    public function getLastName(): string
    {
        return $this->last;
    }

    /**
     * Access Method
     *
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Access Method
     *
     */
    public function getSuffix(): string
    {
        return $this->suffix;
    }

    /**
     * Access Method
     *
     */
    public function isNotParseable(): bool
    {
        return $this->notParseable;
    }

    /**
     * Mutator Method
     * @param string $newFullName the new value to set fullName to
     */
    public function setFullName(string $newFullName): self
    {
        $this->fullName = $newFullName;

        return $this;
    }

    /**
     * Determine if the needle is in the haystack.
     *
     * @param mixed $needle the needle to look for
     * @param array $haystack the haystack from which to look into
     *
     */
    private function inArrayNorm($needle, array $haystack): bool
    {
        $needle = trim(strtolower(str_replace('.', '', $needle)));

        return in_array($needle, $haystack);
    }

    /**
     * Extract the elements of the full name into separate parts.
     *
     */
    public function parse(): self
    {
        // reset values
        $this->title = "";
        $this->first = "";
        $this->middle = "";
        $this->last = "";
        $this->suffix = "";
        $this->notParseable = false;

        // break up name based on number of commas
        $pieces = explode(',', preg_replace('/\s+/', ' ', trim($this->fullName)));
        $numPieces = count($pieces);

        switch ($numPieces) {

            // array(title first middle last suffix)
            case 1:
                $subPieces = explode(' ', trim($pieces[0]));
                $numSubPieces = count($subPieces);
                for ($i = 0; $i < $numSubPieces; $i++) {
                    $current = trim($subPieces[$i]);
                    if ($i < ($numSubPieces - 1)) {
                        $next = trim($subPieces[$i + 1]);
                    } else {
                        $next = "";
                    }
                    if ($i == 0 && $this->inArrayNorm($current, $this->titles)) {
                        $this->title = $current;
                        continue;
                    }
                    if ($this->first == "") {
                        $this->first = $current;
                        continue;
                    }
                    if ($i == $numSubPieces - 2 && ($next != "") && $this->inArrayNorm($next, $this->suffices)) {
                        if ($this->last != "") {
                            $this->last .= " " . $current;
                        } else {
                            $this->last = $current;
                        }
                        $this->suffix = $next;
                        break;
                    }
                    if ($i == $numSubPieces - 1) {
                        if ($this->last != "") {
                            $this->last .= " " . $current;
                        } else {
                            $this->last = $current;
                        }
                        continue;
                    }
                    if ($this->inArrayNorm($current, $this->prefices)) {
                        if ($this->last != "") {
                            $this->last .= " " . $current;
                        } else {
                            $this->last = $current;
                        }
                        continue;
                    }
                    if ($next == 'y' || $next == 'Y') {
                        if ($this->last != "") {
                            $this->last .= " " . $current;
                        } else {
                            $this->last = $current;
                        }
                        continue;
                    }
                    if ($this->last != "") {
                        $this->last .= " " . $current;
                        continue;
                    }
                    if ($this->middle != "") {
                        $this->middle .= " " . $current;
                    } else {
                        $this->middle = $current;
                    }
                }
                break;

            default:
                switch ($this->inArrayNorm($pieces[1], $this->suffices)) {

                    // array(title first middle last, suffix [, suffix])
                    case true:
                        $subPieces = explode(' ', trim($pieces[0]));
                        $numSubPieces = count($subPieces);
                        for ($i = 0; $i < $numSubPieces; $i++) {
                            $current = trim($subPieces[$i]);
                            if ($i < ($numSubPieces - 1)) {
                                $next = trim($subPieces[$i + 1]);
                            } else {
                                $next = "";
                            }
                            if ($i == 0 && $this->inArrayNorm($current, $this->titles)) {
                                $this->title = $current;
                                continue;
                            }
                            if ($this->first == "") {
                                $this->first = $current;
                                continue;
                            }
                            if ($i == $numSubPieces - 1) {
                                if ($this->last != "") {
                                    $this->last .= " " . $current;
                                } else {
                                    $this->last = $current;
                                }
                                continue;
                            }
                            if ($this->inArrayNorm($current, $this->prefices)) {
                                if ($this->last != "") {
                                    $this->last .= " " . $current;
                                } else {
                                    $this->last = $current;
                                }
                                continue;
                            }
                            if ($next == 'y' || $next == 'Y') {
                                if ($this->last != "") {
                                    $this->last .= " " . $current;
                                } else {
                                    $this->last = $current;
                                }
                                continue;
                            }
                            if ($this->last != "") {
                                $this->last .= " " . $current;
                                continue;
                            }
                            if ($this->middle != "") {
                                $this->middle .= " " . $current;
                            } else {
                                $this->middle = $current;
                            }
                        }
                        $this->suffix = trim($pieces[1]);
                        for ($i = 2; $i < $numPieces; $i++) {
                            $this->suffix .= ", " . trim($pieces[$i]);
                        }
                        break;

                    // array(last, title first middles[,] suffix [,suffix])
                    case false:
                        $subPieces = explode(' ', trim($pieces[1]));
                        $numSubPieces = count($subPieces);
                        for ($i = 0; $i < $numSubPieces; $i++) {
                            $current = trim($subPieces[$i]);
                            if ($i < ($numSubPieces - 1)) {
                                $next = trim($subPieces[$i + 1]);
                            } else {
                                $next = "";
                            }
                            if ($i == 0 && $this->inArrayNorm($current, $this->titles)) {
                                $this->title = $current;
                                continue;
                            }
                            if ($this->first == "") {
                                $this->first = $current;
                                continue;
                            }
                            if ($i == $numSubPieces - 2 && ($next != "") && $this->inArrayNorm($next, $this->suffices)) {
                                if ($this->middle != "") {
                                    $this->middle .= " " . $current;
                                } else {
                                    $this->middle = $current;
                                }
                                $this->suffix = $next;
                                break;
                            }
                            if ($i == $numSubPieces - 1 && $this->inArrayNorm($current, $this->suffices)) {
                                $this->suffix = $current;
                                continue;
                            }
                            if ($this->middle != "") {
                                $this->middle .= " " . $current;
                            } else {
                                $this->middle = $current;
                            }
                        }
                        if (isset($pieces[2]) && $pieces[2]) {
                            if ($this->last == "") {
                                $this->suffix = trim($pieces[2]);
                                for ($s = 3; $s < $numPieces; $s++) {
                                    $this->suffix .= ", " . trim($pieces[$s]);
                                }
                            } else {
                                for ($s = 2; $s < $numPieces; $s++) {
                                    $this->suffix .= ", " . trim($pieces[$s]);
                                }
                            }
                        }
                        $this->last = $pieces[0];
                        break;
                }
                unset($pieces);
                break;
        }
        if ($this->first == "" && $this->middle == "" && $this->last == "") {
            $this->notParseable = true;
        }
        $explodeMiddle = explode(' ', $this->middle);
        if (count($explodeMiddle) == 2 && strrpos($this->middle, '.') === false) {
            $this->first .= " " . array_shift($explodeMiddle);
            $this->middle = reset($explodeMiddle);
        }

        return $this;
    }

    /**
     *
     * returns the json config
     */
    protected function getJsonParserConfig(): string
    {

        return '{
            "language": [
              "FR",
              "EN",
              "ES",
              "DE",
              "IT"
            ],
            "titles": [
              "mme",
              "mlle",
              "m.",
              "mademoiselle",
              "madame",
              "monsieur",
              "fr.",
              "frl.",
              "hr.",
              "frau",
              "fräulein",
              "herr",
              "sig.ra",
              "Sig.na",
              "sig.",
              "signore",
              "signora",
              "signorina",
              "señorita",
              "srta.",
              "sra.",
              "señora",
              "señor",
              "sr.",
              "dr",
              "doctor",
              "miss",
              "misses",
              "mr",
              "mister",
              "mrs",
              "ms",
              "judge",
              "sir",
              "madam",
              "madame",
              "AB",
              "2ndLt",
              "Amn",
              "1stLt",
              "A1C",
              "Capt",
              "SrA",
              "Maj",
              "SSgt",
              "LtCol",
              "TSgt",
              "Col",
              "BrigGen",
              "1stSgt",
              "MajGen",
              "SMSgt",
              "LtGen",
              "1stSgt",
              "Gen",
              "CMSgt",
              "1stSgt",
              "CCMSgt",
              "CMSAF",
              "PVT",
              "2LT",
              "PV2",
              "1LT",
              "PFC",
              "CPT",
              "SPC",
              "MAJ",
              "CPL",
              "LTC",
              "SGT",
              "COL",
              "SSG",
              "BG",
              "SFC",
              "MG",
              "MSG",
              "LTG",
              "1SGT",
              "GEN",
              "SGM",
              "CSM",
              "SMA",
              "WO1",
              "WO2",
              "WO3",
              "WO4",
              "WO5",
              "ENS",
              "SA",
              "LTJG",
              "SN",
              "LT",
              "PO3",
              "LCDR",
              "PO2",
              "CDR",
              "PO1",
              "CAPT",
              "CPO",
              "RADM(LH)",
              "SCPO",
              "RADM(UH)",
              "MCPO",
              "VADM",
              "MCPOC",
              "ADM",
              "MPCO-CG",
              "CWO-2",
              "CWO-3",
              "CWO-4",
              "Pvt",
              "2ndLt",
              "PFC",
              "1stLt",
              "LCpl",
              "Capt",
              "Cpl",
              "Maj",
              "Sgt",
              "LtCol",
              "SSgt",
              "Col",
              "GySgt",
              "BGen",
              "MSgt",
              "MajGen",
              "1stSgt",
              "LtGen",
              "MGySgt",
              "Gen",
              "SgtMaj",
              "SgtMajMC",
              "WO-1",
              "CWO-2",
              "CWO-3",
              "CWO-4",
              "CWO-5",
              "ENS",
              "SA",
              "LTJG",
              "SN",
              "LT",
              "PO3",
              "LCDR",
              "PO2",
              "CDR",
              "PO1",
              "CAPT",
              "CPO",
              "RDML",
              "SCPO",
              "RADM",
              "MCPO",
              "VADM",
              "MCPON",
              "ADM",
              "FADM",
              "WO1",
              "CWO2",
              "CWO3",
              "CWO4",
              "CWO5"
            ],
            "prefices": [
              "bon",
              "ben",
              "bin",
              "da",
              "dal",
              "de",
              "del",
              "der",
              "de",
              "e",
              "la",
              "le",
              "san",
              "st",
              "ste",
              "van",
              "vel",
              "von"
            ],
            "suffices": [
              "esq",
              "esquire",
              "jr",
              "sr",
              "2",
              "i",
              "ii",
              "iii",
              "iv",
              "v",
              "clu",
              "chfc",
              "cfp",
              "md",
              "phd"
            ]
          }';
    }
}

