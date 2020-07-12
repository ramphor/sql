<?php

/**
 *  MIT License
 *
 *  Copyright (c) 2017 Trevor Herselman <therselman@gmail.com>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *  SOFTWARE.
 */

namespace Twister;

/**
 *  Raw SQL Query Builder
 *
 *  @author      Trevor Herselman <therselman@gmail.com>
 *  @copyright   Copyright (c) 2017 Trevor Herselman
 *  @license     http://opensource.org/licenses/MIT
 *  @link        https://github.com/twister-php/sql
 *  @api
 */
class Sql
{
    /**
     *  The magic is contained within ...
     *
     *  @var string|null
     */
    protected $sql              =   null;


    /**
     *  Quote style to use for strings.
     *  Can be either `'"'` or `"'"`
     *
     *  @var string
     */
    protected static $quot      =   '"';


    /**
     *  (optional) Database connection
     *
     *  Used to change the string escaping rules
     *  eg. MySQL and PostgreSQL have different escaping rules!
     *
     *  @var object|null
     */
    protected static $conn      =   null;


    /**
     *  (optional) String 'escaping' handler
     *
     *  By default uses a MySQL compatible handler
     *
     *  To enable a database specific handler, set the database connection with:
     *
     *      \Twister\Sql::setConnection($db)
     *
     *  MySQL:       {@link https://dev.mysql.com/doc/refman/5.7/en/string-literals.html}
     *  PostgreSQL : {@link https://www.postgresql.org/docs/9.2/static/sql-syntax-lexical.html}
     *
     *  @var callable
     */
    protected static $escape_handler    =   '\\Twister\\Sql::default_escape_string';


    /**
     *  (optional) String 'quoteing' handler
     *
     *  By default uses a MySQL compatible handler
     *
     *  To enable a database specific handler, set the database connection with:
     *
     *      \Twister\Sql::setConnection($db)
     *
     *  MySQL:       {@link https://dev.mysql.com/doc/refman/5.7/en/string-literals.html}
     *  PostgreSQL : {@link https://www.postgresql.org/docs/9.2/static/sql-syntax-lexical.html}
     *
     *  @var callable
     */
    protected static $quote_handler     =   '\\Twister\\Sql::default_quote_string';


    /**
     *  @var callable
     */
    protected static $exec      =   '\\Twister\\Sql::noConnError';


    /**
     *  @var callable
     */
    protected static $execute   =   '\\Twister\\Sql::noConnError';


    /**
     *  @var callable
     */
    protected static $query     =   '\\Twister\\Sql::noConnError';


    /**
     *  @var callable
     */
    protected static $lookup    =   '\\Twister\\Sql::noConnError';


    /**
     *  @var callable
     */
    protected static $fetchAll  =   '\\Twister\\Sql::noConnError';


    /**
     *  @var callable
     */
    protected static $fetchAllIndexed   =   '\\Twister\\Sql::noConnError';


    /**
     *  @var callable
     */
    protected static $fetchNum  =   '\\Twister\\Sql::noConnError';


    /**
     *  Custom text modifiers eg. :dump, :log,
     *
     *  This is an array of callback functions that handle custom modifiers.
     *
     *  This is a fully working feature, but not thoroughly documented because it's not likely to get much attention
     *
     *  @var callable[]|null
     */
    protected static $modifiers =   null;


    /**
     *  Custom data types eg. %usetheforce, %created
     *
     *  This is an array of callback functions that handle custom data types.
     *
     *  This is a fully working feature, but not thoroughly documented because it's not likely to get much attention
     *
     *  @var callable[]|null
     */
    protected static $types     =   null;


    /**
     *  Contains the list of SQL reserved words with some formatting
     *
     *  This list can be modified internally with singleLineStatements() and lowerCaseStatements()
     *
     *  `Twister\Sql::singleLineStatements()`
     *      will create single line results (replacing \s+ with ' ') for use with the console / command prompt
     *
     *  `Twister\Sql::lowerCaseStatements()`
     *      will set all these values to 'lower case' for those that prefer it
     *
     *  @var string[] translations
     */
    protected static $translations  =   [
            'EXPLAIN'       =>  'EXPLAIN ',
            'SELECT'        =>  'SELECT ',
            'DELETE'        =>  'DELETE ',
            'INSERT'        =>  'INSERT ',
            'UPDATE'        =>  'UPDATE ',
            'CALL'          =>  'CALL ',

            'INSERT_INTO'   =>  'INSERT INTO ',
            'INSERTINTO'    =>  'INSERT INTO ',
            'DELETE_FROM'   =>  'DELETE FROM ',
            'DELETEFROM'    =>  'DELETE FROM ',

            'SELECT_ALL'    =>  'SELECT *',
            'SA'            =>  'SELECT *',
            'SALL'          =>  'SELECT *',
            'S_ALL'         =>  'SELECT *',

            'S_CACHE'       =>  'SELECT SQL_CACHE ',
            'S_NCACHE'      =>  'SELECT SQL_NO_CACHE ',
            'S_NO_CACHE'    =>  'SELECT SQL_NO_CACHE ',

            'SELECT_DISTINCT'=> 'SELECT DISTINCT ',
            'SD'            =>  'SELECT DISTINCT ',             //  (S)ELECT (D)ISTINCT
            'SDA'           =>  'SELECT DISTINCT * ',           //  (S)ELECT (D)ISTINCT (A)LL
            'SDCA'          =>  'SELECT DISTINCT COUNT(*) ',    //  (S)ELECT (D)ISTINCT (C)OUNT (A)LL
            'SDCAS'         =>  'SELECT DISTINCT COUNT(*) AS ', //  (S)ELECT (D)ISTINCT (C)OUNT (A)S
            'SDCAA'         =>  'SELECT DISTINCT COUNT(*) AS ', //  (S)ELECT (D)ISTINCT (C)OUNT (A)LL (A)S
            'SDCAAS'        =>  'SELECT DISTINCT COUNT(*) AS ', //  (S)ELECT (D)ISTINCT (C)OUNT (A)LL (A)S
            'SDAF'          =>  'SELECT DISTINCT COUNT(*) FROM ',// (S)ELECT (D)ISTINCT (C)OUNT (A)LL (F)ROM

            //  compound statements
            'SAF'           =>  'SELECT *' . PHP_EOL . 'FROM' . PHP_EOL . "\t",
            'SELECT_ALL_FROM'=> 'SELECT *' . PHP_EOL . 'FROM' . PHP_EOL . "\t",
            'SCAF'          =>  'SELECT COUNT(*)' . PHP_EOL . 'FROM' . PHP_EOL . "\t",

            'SC'            =>  'SELECT COUNT(*)',      //  SA = (S)ELECT (C)OUNT (ALL) is implied here
            'SC_AS'         =>  'SELECT COUNT(*) AS ',  //  SA = (S)ELECT (C)OUNT (ALL) is implied here
            'SCA'           =>  'SELECT COUNT(*)',      //  SA = (S)ELECT (C)OUNT (A)LL
            'SCAA'          =>  'SELECT COUNT(*) AS',   //  SA = (S)ELECT (C)OUNT (A)LL (A)S
            'SCA_AS'        =>  'SELECT COUNT(*) AS',   //  SA = (S)ELECT (C)OUNT (A)LL
            'S_COUNT_ALL'   =>  'SELECT COUNT(*)',
            'S_COUNT_ALL_AS'=>  'SELECT COUNT(*) AS ',
            'SELECT_CA'     =>  'SELECT COUNT(*)',      //  CA = (C)OUNT (A)LL = COUNT(*)
            'SELECT_CA_AS'  =>  'SELECT COUNT(*) AS ',
            'SELECT_CALL'   =>  'SELECT COUNT(*)',
            'SELECT_CALL_AS'=>  'SELECT COUNT(*) AS ',
            'SELECT_COUNT_ALL'=>'SELECT COUNT(*)',
            'SELECT_COUNT_ALL_AS'=>'SELECT COUNT(*) AS ',

            'CREATE'        =>  'CREATE ',
            'DROP'          =>  'DROP ',
            'CREATE_TABLE'  =>  'CREATE TABLE ',
            'ALTER'         =>  'ALTER ',
            'ALTER_TABLE'   =>  'ALTER TABLE ',
            'ALTER_DATABASE'=>  'ALTER DATABASE ',
            'ALTER_SCHEMA'  =>  'ALTER SCHEMA ',
            'ALTER_EVENT'   =>  'ALTER EVENT ',
            'ALTER_FUNCTION'=>  'ALTER FUNCTION ',
            'DATABASE'      =>  'DATABASE ',
            'SCHEMA'        =>  'SCHEMA ',
            'EVENT'         =>  'EVENT ',
            'FUNCTION'      =>  'FUNCTION ',
            'TABLE'         =>  'TABLE ',

            'ALL'           =>  '*',
            'DISTINCT'      =>  'DISTINCT ',
            'DISTINCTROW'   =>  'DISTINCTROW ',
            'HIGH_PRIORITY' =>  'HIGH_PRIORITY ',
            'HIGH'          =>  'HIGH_PRIORITY ',
            'STRAIGHT_JOIN' =>  'STRAIGHT_JOIN ',
            'SQL_SMALL_RESULT'=>'SQL_SMALL_RESULT ',
            'SMALL'         =>  'SQL_SMALL_RESULT ',
            'SQL_BIG_RESULT'=>  'SQL_BIG_RESULT ',
            'BIG'           =>  'SQL_BIG_RESULT ',
            'SQL_BUFFER_RESULT'=>'SQL_BUFFER_RESULT ',
            'BUFFER'        =>  'SQL_BUFFER_RESULT ',
            'SQL_CACHE'     =>  'SQL_CACHE ',
            'CACHE'         =>  'SQL_CACHE ',
            'SQL_NO_CACHE'  =>  'SQL_NO_CACHE ',
            'NO_CACHE'      =>  'SQL_NO_CACHE ',
            'SQL_CALC_FOUND_ROWS'=> 'SQL_CALC_FOUND_ROWS ',
            'CALC'          =>  'SQL_CALC_FOUND_ROWS ',

            'DELAYED'       =>  'DELAYED ',

            'LOW_PRIORITY'  =>  'LOW_PRIORITY ',
            'LOW'           =>  'LOW_PRIORITY ',
            'QUICK'         =>  'QUICK ',
            'IGNORE'        =>  'IGNORE ',

            'TRUNCATE'      =>  'TRUNCATE ',
            'TRUNCATE_TABLE'=>  'TRUNCATE TABLE ',
            'TT'            =>  'TRUNCATE TABLE ',

            'CA'            =>  'COUNT(*)',
            'CAA'           =>  'COUNT(*) AS ',
            'CA_AS'         =>  'COUNT(*) AS ',
            'COUNT_ALL'     =>  'COUNT(*)',
            'COUNT_ALL_AS'  =>  'COUNT(*) AS ',
            'COUNT'         =>  'COUNT',
            'LAST_INSERT_ID'=>  'LAST_INSERT_ID()',
            'ROW_COUNT'     =>  'ROW_COUNT()',
            'A'             =>  '*',
            'STAR'          =>  '*',

            'FROM'          =>  PHP_EOL . 'FROM'               . PHP_EOL . "\t",
            'JOIN'          =>  PHP_EOL . "\tJOIN"             . PHP_EOL . "\t\t",
            'LEFT_JOIN'     =>  PHP_EOL . "\tLEFT JOIN"        . PHP_EOL . "\t\t",
            'LEFT_OUTER_JOIN'=> PHP_EOL . "\tLEFT OUTER JOIN"  . PHP_EOL . "\t\t",
            'RIGHT_JOIN'    =>  PHP_EOL . "\tRIGHT JOIN"       . PHP_EOL . "\t\t",
            'RIGHT_OUTER_JOIN'=>PHP_EOL . "\tRIGHT OUTER JOIN" . PHP_EOL . "\t\t",
            'INNER_JOIN'    =>  PHP_EOL . "\tINNER JOIN"       . PHP_EOL . "\t\t",
            'OUTER_JOIN'    =>  PHP_EOL . "\tOUTER JOIN"       . PHP_EOL . "\t\t",
            'CROSS_JOIN'    =>  PHP_EOL . "\tCROSS JOIN"       . PHP_EOL . "\t\t",
            'STRAIGHT_JOIN' =>  PHP_EOL . "\tSTRAIGHT_JOIN"    . PHP_EOL . "\t\t",
            'NATURAL_JOIN'  =>  PHP_EOL . "\tNATURAL JOIN"     . PHP_EOL . "\t\t",
            'WHERE'         =>  PHP_EOL . 'WHERE'              . PHP_EOL . "\t",
            'GROUP_BY'      =>  PHP_EOL . 'GROUP BY',
            'HAVING'        =>  PHP_EOL . 'HAVING ',
            'ORDER_BY'      =>  PHP_EOL . 'ORDER BY ',
            'LIMIT'         =>  PHP_EOL . 'LIMIT ',
            'PROCEDURE'     =>  PHP_EOL . 'PROCEDURE ',
            'INTO_OUTFILE'  =>  PHP_EOL . 'INTO OUTFILE ',
            'UNION'         =>  PHP_EOL . 'UNION'          . PHP_EOL,
            'UNION_ALL'     =>  PHP_EOL . 'UNION ALL'      . PHP_EOL,
            'UNION_DISTINCT'=>  PHP_EOL . 'UNION DISTINCT' . PHP_EOL,
            'EXCEPT'        =>  PHP_EOL . 'EXCEPT'         . PHP_EOL,
            'VALUES'        =>  PHP_EOL . 'VALUES'         . PHP_EOL . "\t",
            'ADD'           =>  PHP_EOL . 'ADD ',

            'S'             =>  'SELECT ',
            'D'             =>  'DELETE ',
            'DF'            =>  'DELETE FROM ',
            'I'             =>  'INSERT ',
            'II'            =>  'INSERT INTO ',
            'U'             =>  'UPDATE ',
            'F'             =>  PHP_EOL . 'FROM'               . PHP_EOL . "\t",
            'J'             =>  PHP_EOL . "\tJOIN"             . PHP_EOL . "\t\t",
            'IJ'            =>  PHP_EOL . "\tINNER JOIN"       . PHP_EOL . "\t\t",
            'LJ'            =>  PHP_EOL . "\tLEFT JOIN"        . PHP_EOL . "\t\t",
            'LOJ'           =>  PHP_EOL . "\tLEFT OUTER JOIN"  . PHP_EOL . "\t\t",
            'RJ'            =>  PHP_EOL . "\tRIGHT JOIN"       . PHP_EOL . "\t\t",
            'ROJ'           =>  PHP_EOL . "\tRIGHT OUTER JOIN" . PHP_EOL . "\t\t",
            'OJ'            =>  PHP_EOL . "\tOUTER JOIN"       . PHP_EOL . "\t\t",
            'CJ'            =>  PHP_EOL . "\tCROSS JOIN"       . PHP_EOL . "\t\t",
            'SJ'            =>  PHP_EOL . "\tSTRAIGHT_JOIN"    . PHP_EOL . "\t\t",
            'NJ'            =>  PHP_EOL . "\tNATURAL JOIN"     . PHP_EOL . "\t\t",
            'W'             =>  PHP_EOL . 'WHERE'              . PHP_EOL . "\t",
            'G'             =>  PHP_EOL . 'GROUP BY ',
            'H'             =>  PHP_EOL . 'HAVING ',
            'O'             =>  PHP_EOL . 'ORDER BY ',
            'OB'            =>  PHP_EOL . 'ORDER BY ',
            'L'             =>  PHP_EOL . 'LIMIT ',

            'USING'         =>  ' USING ',
            'USE'           =>  ' USE ',
            'IGNORE'        =>  ' IGNORE ',
            'FORCE'         =>  ' FORCE ',
            'NATURAL'       =>  ' NATURAL ',

            'DESC'          =>  ' DESC',
            'ASC'           =>  ' ASC',
            'IN'            =>  'IN',
            'IN_'           =>  'IN ',
            '_IN'           =>  ' IN',
            '_IN_'          =>  ' IN ',
            'NOT_IN'        =>  'NOT IN',
            'NOT_IN_'       =>  'NOT IN ',
            '_NOT_IN'       =>  ' NOT IN',
            '_NOT_IN_'      =>  ' NOT IN ',
            'NOT'           =>  'NOT',
            'NOT_'          =>  'NOT ',
            '_NOT'          =>  ' NOT',
            '_NOT_'         =>  ' NOT ',
            'NULL'          =>  'NULL',             //  Warning: don't add spaces here, used in several places without spaces!
            'NULL_'         =>  'NULL ',
            '_NULL'         =>  ' NULL',
            '_NULL_'        =>  ' NULL ',
            'IS'            =>  'IS',
            'IS_'           =>  'IS ',
            '_IS'           =>  ' IS',
            '_IS_'          =>  ' IS ',
            'IS_NOT'        =>  'IS NOT',
            'IS_NOT_'       =>  'IS NOT ',
            '_IS_NOT'       =>  ' IS NOT',
            '_IS_NOT_'      =>  ' IS NOT ',
            'IS_NULL'       =>  'IS NULL',
            'IS_NULL_'      =>  'IS NULL ',
            '_IS_NULL'      =>  ' IS NULL',
            '_IS_NULL_'     =>  ' IS NULL ',
            'LIKE'          =>  ' LIKE ',
        //  'LIKE_'         =>  'LIKE ',
        //  '_LIKE'         =>  ' LIKE',
        //  '_LIKE_'        =>  ' LIKE ',
            'NOT_LIKE'      =>  ' NOT LIKE ',
        //  'NOT_LIKE_'     =>  'NOT LIKE ',
        //  '_NOT_LIKE'     =>  ' NOT LIKE',
        //  '_NOT_LIKE_'    =>  ' NOT LIKE ',
            'CHARACTER_SET' =>  ' CHARACTER SET ',
            'CHARACTER'     =>  ' CHARACTER ',
            'INTO_DUMPFILE' =>  ' INTO DUMPFILE ',
            'DUMPFILE'      =>  'DUMPFILE ',
            'OUTFILE'       =>  'OUTFILE ',

            'INTO'          =>  'INTO ',
            'OFFSET'        =>  ' OFFSET ',

            'FOR_UPDATE'                    =>  PHP_EOL . 'FOR UPDATE',
            'LOCK_IN_SHARE_MODE'            =>  ' LOCK IN SHARE MODE',
            'FOR_UPDATE_LOCK_IN_SHARE_MODE' =>  PHP_EOL . 'FOR UPDATE LOCK IN SHARE MODE',

            'ON_DUPLICATE_KEY_UPDATE'       =>  PHP_EOL . 'ON DUPLICATE KEY UPDATE' . PHP_EOL . "\t",

            'AUTO_INCREMENT'=>  ' AUTO_INCREMENT',
            'INT'           =>  ' INT',
            'PK'            =>  'PRIMARY KEY ',
            'PRIMARY_KEY'   =>  'PRIMARY KEY ',
            'UNIQUE_KEY'    =>  'UNIQUE KEY ',
            'ENGINE'        =>  PHP_EOL . 'ENGINE',

            'IF'            =>  ' IF ',
            'SET'           =>  ' SET ',

            'COMMA'         =>  ', ',
            'C'             =>  ', ',

            '_'             =>  ' ',
            '__'            =>  ', ',
            'Q'             =>  '"',
            'SPACE'         =>  ' ',
            'SP'            =>  ' ',
            '_O'            =>  '(',
            'C_'            =>  ')',
            'OPEN'          =>  '(',
            'CLOSE'         =>  ')',
            'TAB'           =>  "\t",
            'NL'            =>  "\n",
            'CR'            =>  "\r",
            'EOL'           =>  PHP_EOL,
            'BR'            =>  PHP_EOL,
            'EQ'            =>  '=',
            'EQ_'           =>  '= ',
            '_EQ'           =>  ' =',
            '_EQ_'          =>  ' = ',
            'NEQ'           =>  '!=',
            'NEQ_'          =>  '!= ',
            '_NEQ'          =>  ' !=',
            '_NEQ_'         =>  ' != ',
            'NOTEQ'         =>  '!=',
            'NOTEQ_'        =>  '!= ',
            '_NOTEQ'        =>  ' !=',
            '_NOTEQ_'       =>  ' != ',
            'NOT_EQ'        =>  '!=',
            'NOT_EQ_'       =>  '!= ',
            '_NOT_EQ'       =>  ' !=',
            '_NOT_EQ_'      =>  ' != ',
            'GT'            =>  '>',
            'GT_'           =>  '> ',
            '_GT'           =>  ' >',
            '_GT_'          =>  ' > ',
            'GE'            =>  '>=',
            'GE_'           =>  '>= ',
            '_GE'           =>  ' >=',
            '_GE_'          =>  ' >= ',
            'GTEQ'          =>  '>=',
            'GTEQ_'         =>  '>= ',
            '_GTEQ'         =>  ' >=',
            '_GTEQ_'        =>  ' >= ',
            'LT'            =>  '<',
            'LT_'           =>  '< ',
            '_LT'           =>  ' <',
            '_LT_'          =>  ' < ',
            'LE'            =>  '<=',
            'LE_'           =>  '<= ',
            '_LE'           =>  ' <=',
            '_LE_'          =>  ' <= ',
            'LTEQ'          =>  '<=',
            'LTEQ_'         =>  '<= ',
            '_LTEQ'         =>  ' <=',
            '_LTEQ_'        =>  ' <= ',
            'AS'            =>  ' AS ',         //  had to make changes here!
        //  'AS_'           =>  'AS ',
        //  '_AS'           =>  ' AS',
        //  '_AS_'          =>  ' AS ',
            'ON'            =>  ' ON ',         //  had to make changes here!
        //  'ON_'           =>  'ON ',
        //  '_ON'           =>  ' ON',
        //  '_ON_'          =>  ' ON ',
            'AND'           =>  ' AND ',        //  had to make changes here!
        //  'AND_'          =>  'AND ',
        //  '_AND'          =>  ' AND',
        //  '_AND_'         =>  ' AND ',
            'OR'            =>  ' OR ',         //  had to make changes here!
        //  'OR_'           =>  'OR ',
        //  '_OR'           =>  ' OR',
        //  '_OR_'          =>  ' OR ',
            'XOR'           =>  ' XOR ',
        //  'XOR_'          =>  'XOR ',
        //  '_XOR'          =>  ' XOR',
        //  '_XOR_'         =>  ' XOR ',
            'ADD'           =>  '+',
            'ADD_'          =>  '+ ',
            '_ADD'          =>  ' +',
            '_ADD_'         =>  ' + ',
            'SUB'           =>  '-',
            'SUB_'          =>  '- ',
            '_SUB'          =>  ' -',
            '_SUB_'         =>  ' - ',
            'NEG'           =>  '-',
            'NEG_'          =>  '- ',
            '_NEG'          =>  ' -',
            '_NEG_'         =>  ' - ',
            'MUL'           =>  '*',
            'MUL_'          =>  '* ',
            '_MUL'          =>  ' *',
            '_MUL_'         =>  ' * ',
            'DIV'           =>  '/',
            'DIV_'          =>  '/ ',
            '_DIV'          =>  ' /',
            '_DIV_'         =>  ' / ',
            'MOD'           =>  '%',
            'MOD_'          =>  '% ',
            '_MOD'          =>  ' %',
            '_MOD_'         =>  ' % ',

            'MATCH'         =>  'MATCH',
            'MATCH_'        =>  'MATCH ',
            '_MATCH'        =>  ' MATCH',
            '_MATCH_'       =>  ' MATCH ',

            'AFTER'         =>  'AFTER',
            'AFTER_'        =>  'AFTER ',
            '_AFTER'        =>  ' AFTER',
            '_AFTER_'       =>  ' AFTER ',

            '_0_'           =>  '0',    '_0'            =>  '0',
            '_1_'           =>  '1',    '_1'            =>  '1',
            '_2_'           =>  '2',    '_2'            =>  '2',
            '_3_'           =>  '3',    '_3'            =>  '3',
            '_4_'           =>  '4',    '_4'            =>  '4',
            '_5_'           =>  '5',    '_5'            =>  '5',
            '_6_'           =>  '6',    '_6'            =>  '6',
            '_7_'           =>  '7',    '_7'            =>  '7',
            '_8_'           =>  '8',    '_8'            =>  '8',
            '_9_'           =>  '9',    '_9'            =>  '9',
            '_10_'          =>  '10',   '_10'           =>  '10',
            '_11_'          =>  '11',   '_11'           =>  '11',
            '_12_'          =>  '12',   '_12'           =>  '12',
            '_13_'          =>  '13',   '_13'           =>  '13',
            '_14_'          =>  '14',   '_14'           =>  '14',
            '_15_'          =>  '15',   '_15'           =>  '15',
            '_16_'          =>  '16',   '_16'           =>  '16',
            '_17_'          =>  '17',   '_17'           =>  '17',
            '_18_'          =>  '18',   '_18'           =>  '18',
            '_19_'          =>  '19',   '_19'           =>  '19',
            '_20_'          =>  '20',   '_20'           =>  '20',
            '_21_'          =>  '21',   '_21'           =>  '21',
            '_22_'          =>  '22',   '_22'           =>  '22',
            '_23_'          =>  '23',   '_23'           =>  '23',
            '_24_'          =>  '24',   '_24'           =>  '24',
            '_25_'          =>  '25',   '_25'           =>  '25',
            '_26_'          =>  '26',   '_26'           =>  '26',
            '_27_'          =>  '27',   '_27'           =>  '27',
            '_28_'          =>  '28',   '_28'           =>  '28',
            '_29_'          =>  '29',   '_29'           =>  '29',

            '_30_'          =>  '30', '_35_' => '35', '_40_' => '40', '_45_' => '45', '_50_' => '50',
            '_55_'          =>  '55', '_60_' => '60', '_65_' => '65', '_70_' => '70', '_75_' => '75',
            '_80_'          =>  '80', '_85_' => '85', '_90_' => '90', '_95_' => '95', '_100_' => '100',

            '_30'           =>  '30', '_35_' => '35', '_40_' => '40', '_45_' => '45', '_50_' => '50',
            '_55'           =>  '55', '_60_' => '60', '_65_' => '65', '_70_' => '70', '_75_' => '75',
            '_80'           =>  '80', '_85_' => '85', '_90_' => '90', '_95_' => '95', '_100_' => '100',

            'BETWEEN'       =>  ' BETWEEN ',
            '_BETWEEN_'     =>  ' BETWEEN ',

            'OUT'           =>  'OUT ',
            '_OUT_'         =>  ' OUT ',
            'INOUT'         =>  'INOUT ',
            '_INOUT_'       =>  ' INOUT ',

            'PARTITION'     =>  PHP_EOL . 'PARTITION ',
            'WITH_ROLLUP'   =>  ' WITH ROLLUP ',
            'DEFAULT'       =>  ' DEFAULT ',
        ];


    /**************************************************************************/
    /**                           __construct()                              **/
    /**************************************************************************/


    /**
     *  Construct a new SQL statement, initialized by the optional $stmt string
     *      and an optional list of associated $params
     *
     *  Can be full or partial queries, fragments or statements
     *
     *  No syntax checking is done
     *
     *  The object can be initialized in multiple ways:
     *      but operates similar to `sprintf()` and `PDO::prepare()`
     *
     *  The object can be initialized in multiple ways:
     *      but operates very much like `sprintf()` or `PDO::prepare()`
     *
     *  Basic examples:
     *
     *      @ = raw data placeholder - no escaping or quotes
     *      ? = type is auto detected, null = 'NULL', bool = 0/1, strings are escaped & quoted
     *
     *      $sql = sql();
     *      $sql = sql('@', $raw);                          //  @ = raw output - no escaping or quotes
     *      $sql = sql('?', $mixed);                        //  ? = strings are escaped & quoted
     *      $sql = sql('Hello @', 'World');                 //  'Hello World'
     *      $sql = sql('Hello ?', 'World');                 //  'Hello "World"'
     *      $sql = sql('age >= @', 18);                     //  age >= 18
     *      $sql = sql('age >= ?', 18);                     //  age >= 18
     *      $sql = sql('age >= ?', '18');                   //  age >= 18  (is_numeric('18') === true)
     *      $sql = sql('age IS ?', null);                   //  age IS NULL
     *      $sql = sql('SELECT * FROM users');
     *      $sql = sql('SELECT * FROM users WHERE id = ?', $id);
     *      $sql = sql('SELECT @', 'CURDATE()');            //  SELECT CURDATE()    - @ = raw output
     *      $sql = sql('SELECT ?', 'CURDATE()');            //  SELECT "CURDATE()"  - ? = incorrectly escaped
     *
     *  Examples with output:
     *
     *      $sql = sql();
     *      echo $sql;                                      //  `sql()` returns nothing until given commands
     *
     *      echo sql();                                     //  `sql()` starts as an empty string
     *
     *      echo sql('Hello @', 'World');                   //  @ = raw value, no escapes or quotes
     *      Hello World
     *
     *      echo sql('Hello ?', 'World\'s');                //  ? = escaped and quoted
     *      or
     *      echo sql('Hello ?', "World's");                 //  ? = escaped and quoted
     *      Hello "World\'s"
     *
     *      echo sql('age >= @', 18);                       //  @ = raw value, no escapes or quotes
     *      age >= 18
     *
     *      echo sql('age >= ?', 18);                       //  is_numeric(18) === true
     *      age >= 18
     *
     *      echo sql('age >= ?', '18');                     //  is_numeric('18') === true
     *      age >= 18
     *
     *      echo sql('name IS @', null);                    //  @ null = ''
     *      name IS
     *
     *      echo sql('name IS ?', null);                    //  ? null = 'NULL'
     *      name IS NULL
     *
     *      echo sql('dated = @', 'CURDATE()');             //  @ = raw value, no escapes or quotes
     *      date = CURDATE()
     *
     *      echo sql('dated = ?', 'CURDATE()');             //  ? = escaped and quoted
     *      date = "CURDATE()"
     *
     *      echo sql('SELECT * FROM users');
     *      SELECT * FROM users
     *
     *      $id = 5;
     *      echo sql('SELECT * FROM users WHERE id = ?', $id);
     *      SELECT * FROM users WHERE id = 5
     *
     *      $name = "Trevor's Revenge";
     *      echo sql('SELECT * FROM users WHERE name = ?', $name);
     *      SELECT * FROM users WHERE name = "Trevor\'s Revenge"    //  UTF-8 aware escapes
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *                      {@see self::prepare()} for syntax rules
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return void
     */
    public function __construct($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql = $stmt;
        } else {
            $this->prepare($stmt, ...$params);
        }
    }


    /**************************************************************************/
    /**                            __toString()                              **/
    /**************************************************************************/


    /**
     *  __toString() Magic Method
     *
     *  {@link http://php.net/manual/en/language.oop5.magic.php#object.tostring}
     *
     *  @return string $this->sql
     */
    public function __toString()
    {
        return $this->sql;
    }


    /**************************************************************************/
    /**                             __invoke()                               **/
    /**************************************************************************/


    /**
     *  __invoke() Magic Method
     *
     *  @alias prepare()
     *
     *  See {@see prepare()} for optional syntax rules
     *
     *  {@link http://php.net/manual/en/language.oop5.magic.php#object.invoke}
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function __invoke($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= is_null($stmt) ? self::$translations['NULL'] : $stmt;
            return $this;
        }
        return $this->prepare($stmt, ...$params);
    }


    /**************************************************************************/
    /**                               reset()                                **/
    /**************************************************************************/


    /**
     *  start new statement
     *
     *  @alias new()
     *
     *  See {@see prepare()} for optional syntax rules
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function reset($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql = $stmt;
            return $this;
        }
        $this->sql = null;
        return $this->prepare($stmt, ...$params);
    }


    /**************************************************************************/
    /**                         CALL storedProc                              **/
    /**************************************************************************/


    /**
     *  CALL Stored Procudure
     *
     *  This function has the ability to auto-detect if you've
     *      pre-prepared the format for individual values or not;
     *
     *      eg. ->call('sp_name(?, ?, @)', $v1, $v2, $v3)
     *      or  ->call('sp_name', $v1, $v2, $v3)
     *
     *  Both methods are supported.
     *
     *  The function can automatically generate the required parameter list for you!
     *      This is useful if you don't have any special handling requirements
     *
     *  To disable value escaping, use one of the following techniques:
     *          ->call('sp_name(LAST_INSERT_ID(), @, @, ?)', 'u.name', '@sql_variable', $name)
     *          ->call('sp_name', ['@' => 'LAST_INSERT_ID()'])
     *          ->call('sp_name(@, ?)', 'LAST_INSERT_ID()', $name)
     *          ->call('SELECT sp_name(@, ?)', 'LAST_INSERT_ID()', $name)
     *          ->call('SELECT sp_name(LAST_INSERT_ID(), ?)', $name)
     *
     *  Docs:
     *      PDO:        {@link http://php.net/manual/en/pdo.prepared-statements.php}
     *      MySQL:      {@link https://dev.mysql.com/doc/refman/5.7/en/call.html}
     *      PostgreSQL: {@link https://www.postgresql.org/docs/9.1/static/sql-syntax-calling-funcs.html}
     *
     *  SQL Syntax:
     *      MySQL:
     *          CALL sp_name([parameter[,...]])
     *          CALL sp_name[()]
     *      PostgreSQL:
     *          SELECT insert_user_ax_register(...);
     *      PDO:
     *          $stmt = $pdo->prepare("CALL sp_returns_string(?)");
     *          $stmt->bindParam(1, $return_value, PDO::PARAM_STR, 4000);
     *          $stmt->execute();
     *
     *  @todo Possibly detect the connection type; and use the appropriate syntax; because PostgreSQL uses `SELECT sp_name(...)`
     *
     *  @param  string $sp_name Stored procedure name, or pre-prepared string
     *
     *  @param  mixed  ...$params  Parameters required for the stored procedure
     *
     *  @return $this
     */
    public function call($sp_name = null, ...$params)
    {
        if (strpos($sp_name, '(') === false) {
            return $this->prepare('CALL ' . $sp_name, ...$params);
        }
        return $this->prepare('CALL ' . $sp_name . '(' . (count($params) > 0 ? '?' . str_repeat(', ?', count($params) - 1) : null) . ')', ...$params);
    }


    /**
     *  CALL Stored Procudure - shorthand for `call()`
     *
     *  @alias call()
     *
     *  @param  string $sp_name Stored procedure name, or pre-prepared string
     *
     *  @param  mixed  ...$params  Parameters required for the stored procedure
     *
     *  @return $this
     */
    public function c($sp_name = null, ...$params)
    {
        if (strpos($sp_name, '(') === false) {
            return $this->prepare('CALL ' . $sp_name, ...$params);
        }
        return $this->prepare('CALL ' . $sp_name . '(' . (count($params) > 0 ? '?' . str_repeat(', ?', count($params) - 1) : null) . ')', ...$params);
    }


    /**
     *  CALL Stored Procedure
     *
     *  @alias call()
     *
     *  @param  string $sp_name Stored procedure name, or pre-prepared string
     *
     *  @param  mixed  ...$params  Parameters required for the stored procedure
     *
     *  @return $this
     */
    public function storedProc($sp_name = null, ...$params)
    {
        if (strpos($sp_name, '(') === false) {
            return $this->prepare('CALL ' . $sp_name, ...$params);
        }
        return $this->prepare('CALL ' . $sp_name . '(' . (count($params) > 0 ? '?' . str_repeat(', ?', count($params) - 1) : null) . ')', ...$params);
    }


    /**
     *  CALL Stored Procudure - shorthand for `storedProc()`
     *
     *  @alias storedProc()
     *
     *  @param  string $sp_name Stored procedure name, or pre-prepared string
     *
     *  @param  mixed  ...$params  Parameters required for the stored procedure
     *
     *  @return $this
     */
    public function sp($sp_name = null, ...$params)
    {
        if (strpos($sp_name, '(') === false) {
            return $this->prepare('CALL ' . $sp_name, ...$params);
        }
        return $this->prepare('CALL ' . $sp_name . '(' . (count($params) > 0 ? '?' . str_repeat(', ?', count($params) - 1) : null) . ')', ...$params);
    }


    /**************************************************************************/
    /**                             INSERT                                   **/
    /**************************************************************************/


    /**
     *  Generates an SQL `INSERT` statement
     *
     *  See {@see prepare()} for optional syntax rules
     *
     *  eg. `->insert('INTO users ...', ...)`
     *      `INSERT INTO users ...`
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function insert($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['INSERT'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['INSERT'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `INSERT` statement - shorthand for `insert()`
     *
     *  @alias insert()
     *
     *  eg. `->i('INTO users ...', ...)`
     *      `INSERT INTO users ...`
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function i($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['INSERT'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['INSERT'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                          INSERT INTO                                 **/
    /**************************************************************************/


    /**
     *  Generates an SQL `INSERT INTO` statement
     *
     *  See {@see insert_into()} for 'snake case' alternative
     *
     *  eg. `->insertInto('users ...', ...)`
     *      `INSERT INTO users ...`
     *
     *  See {@see into()} for advanced INTO handling rules
     *
     *  See {@see prepare()} for optional syntax rules
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function insertInto($stmt, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['INSERT_INTO'] . $stmt;
            return $this;
        }
        $this->sql .= self::$translations['INSERT'];
        return $this->into($stmt, ...$params);
    }


    /**
     *  Generates an SQL `INSERT INTO` statement
     *
     *  See {@see insertInto()} for 'camel case' alternative
     *
     *  eg. `->insert_into('users ...', ...)`
     *      `INSERT INTO users ...`
     *
     *  See {@see into()} for advanced INTO handling rules
     *
     *  See {@see prepare()} for optional syntax rules
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function insert_into($stmt, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['INSERT_INTO'] . $stmt;
            return $this;
        }
        $this->sql .= self::$translations['INSERT'];
        return $this->into($stmt, ...$params);
    }


    /**
     *  Generates an SQL `INSERT INTO` statement - shorthand for `insertInto()`
     *
     *  This is exactly the same as calling `insertInto()` or `insert_into()`
     *  just conveniently shorter syntax
     *
     *  eg. `->ii('users ...', ...)`
     *      `INSERT INTO users ...`
     *
     *  See {@see into()} for advanced INTO handling rules
     *
     *  @alias insert_into()
     *  @alias insertInto()
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function ii($stmt, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['INSERT_INTO'] . $stmt;
            return $this;
        }
        $this->sql .= self::$translations['INSERT'];
        return $this->into($stmt, ...$params);
    }


    /**
     *  Generates an SQL `INSERT HIGH_PRIORITY INTO` statement
     *
     *  Same as insertInto() except the `HIGH_PRIORITY` modifier is added
     *
     *  eg. `->insertHighPriorityInto('users ...', ...)`
     *      `INSERT HIGH_PRIORITY INTO users ...`
     *
     *  See {@see into()} for advanced INTO handling rules
     *
     *  See {@see insert_high_priority_into()} for 'snake case' alternative
     *
     *  See {@see prepare()} for optional syntax rules
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function insertHighPriorityInto($stmt, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['INSERT'] . self::$translations['HIGH_PRIORITY'] . self::$translations['INTO'] . $stmt;
            return $this;
        }
        $this->sql .= self::$translations['INSERT'] . self::$translations['HIGH_PRIORITY'];
        return $this->into($stmt, ...$params);
    }


    /**
     *  Generates an SQL `INSERT HIGH_PRIORITY INTO` statement
     *
     *  Same as `insert_into()` except for adding the `HIGH_PRIORITY` modifier
     *
     *  eg. `->insert_high_priority_into('users ...', ...)`
     *      `INSERT HIGH_PRIORITY INTO users ...`
     *
     *  See {@see into()} for advanced INTO handling rules
     *
     *  See {@see insertHighPriorityInto()} for 'camel case' alternative
     *
     *  See {@see prepare()} for optional syntax rules
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function insert_high_priority_into($stmt, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['INSERT'] . self::$translations['HIGH_PRIORITY'] . self::$translations['INTO'] . $stmt;
            return $this;
        }
        $this->sql .= self::$translations['INSERT'] . self::$translations['HIGH_PRIORITY'];
        return $this->into($stmt, ...$params);
    }


    /**
     *  Generates an SQL `INSERT IGNORE INTO` statement
     *
     *  Same as `insertInto()` except for adding the `IGNORE` modifier
     *
     *  eg. `->insertIgnoreInto('users ...', ...)`
     *      `INSERT IGNORE INTO users ...`
     *
     *  See {@see into()} for advanced INTO handling rules
     *
     *  See {@see insert_ignore_into()} for 'snake case' alternative
     *
     *  See {@see prepare()} for optional syntax rules
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function insertIgnoreInto($stmt, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['INSERT'] . self::$translations['IGNORE'] . self::$translations['INTO'] . $stmt;
            return $this;
        }
        $this->sql .= self::$translations['INSERT'] . self::$translations['IGNORE'];
        return $this->into($stmt, ...$params);
    }


    /**
     *  Generates an SQL `INSERT IGNORE INTO` statement
     *
     *  Same as `insert_into()` except for adding the `IGNORE` modifier
     *
     *  eg. `->insert_ignore_into('users ...', ...)`
     *      `INSERT IGNORE INTO users ...`
     *
     *  See {@see into()} for advanced INTO handling rules
     *
     *  See {@see insertIgnoreInto()} for 'camel case' alternative
     *
     *  See {@see prepare()} for optional syntax rules
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function insert_ignore_into($stmt, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['INSERT'] . self::$translations['IGNORE'] . self::$translations['INTO'] . $stmt;
            return $this;
        }
        $this->sql .= self::$translations['INSERT'] . self::$translations['IGNORE'];
        return $this->into($stmt, ...$params);
    }


    /**
     *  Generates an SQL `INSERT $modifier INTO` statement
     *
     *  Same as `insertInto()` except adds a custom $modifier between 'INSERT' and 'INTO'
     *
     *  eg. `->insertWithModifierInto('_MY_MODIFIER_', 'users ...');`
     *      `INSERT _MY_MODIFIER_ INTO users ...`
     *
     *  See {@see into()} for advanced INTO handling rules
     *
     *  See {@see insert_with_modifier_into()} for 'snake case' alternative
     *
     *  See {@see prepare()} for optional syntax rules
     *
     *  @param  string      $modifier
     *                      An SQL `INSERT` statement modifier to place between the `INSERT`
     *                      and `INTO` clause; such as `HIGH_PRIORITY` or `IGNORE`
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function insertWithModifierInto($modifier, $stmt, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['INSERT'] . $modifier . ' ' . self::$translations['INTO'] . $stmt;
            return $this;
        }
        $this->sql .= self::$translations['INSERT'] . $modifier . ' ';
        return $this->into($stmt, ...$params);
    }


    /**
     *  Generates an SQL `INSERT $modifier INTO` statement
     *
     *  Same as `insert_into()` except adds a custom $modifier between 'INSERT' and 'INTO'
     *
     *  eg. `->insert_with_modifier_into('_MY_MODIFIER_', 'users ...');`
     *      `INSERT _MY_MODIFIER_ INTO users ...`
     *
     *  See {@see into()} for advanced INTO handling rules
     *
     *  See {@see insertWithModifierInto()} for 'camel case' alternative
     *
     *  See {@see prepare()} for optional syntax rules
     *
     *  @param  string      $modifier
     *                      An SQL `INSERT` statement modifier to place between the `INSERT`
     *                      and `INTO` clause; such as `HIGH_PRIORITY` or `IGNORE`
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function insert_with_modifier_into($modifier, $stmt, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['INSERT'] . $modifier . ' ' . self::$translations['INTO'] . $stmt;
            return $this;
        }
        $this->sql .= self::$translations['INSERT'] . $modifier . ' ';
        return $this->into($stmt, ...$params);
    }


    /**************************************************************************/
    /**                                INTO                                  **/
    /**************************************************************************/


    /**
     *  Generates an SQL `INTO` statement
     *
     *  There are multiple ways to call this method.
     *  The method takes special action depending on whether you supply one, two or mixed arrays
     *
     *  Examples:
     *
     *      ->into('users (col1, col2, dated) VALUES (?, ?, @)', $value1, $value2, 'CURDATE()') //  VERY useful!
     *      ->into('users', ['col1', 'col2', '@dated'])                                         //  not very useful! Just puts the column names in; `@` is stripped from column titles!
     *      ->into('users', ['col1' => 'value1', 'col2' => 'value2', '@dated' => 'CURDATE()'])  //  column names and values can be nicely formatted on multiple lines
     *      ->into('users', ['col1', 'col2', '@dated'], ['value1', 'value2', 'CURDATE()'])      //  convenient style if your values are already in an array
     *      ->into('users', ['col1', 'col2', '@dated'], $value1, $value2, 'CURDATE()')          //  nice ... `dated` column will NOT be escaped!
     *
     *  MySQL INSERT INTO Syntax:   {@link https://dev.mysql.com/doc/refman/5.7/en/insert.html}
     *
     *      INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name [PARTITION (partition_name,...)] [(col_name,...)]  {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
     *
     *  PostgreSQL INSERT INTO Syntax:  {@link https://www.postgresql.org/docs/8.2/static/sql-insert.html}
     *
     *      INSERT INTO table [ ( column [, ...] ) ] { DEFAULT VALUES | VALUES ( { expression | DEFAULT } [, ...] ) [, ...] | query } [ RETURNING * | output_expression [ AS output_name ] [, ...] ]
     *
     *  @param  string    $stmt   Table name or `prepare` style statement
     *  @param  mixed  ...$params Parameters to use, either columns only or column-value pairs
     *  @return $this
     */
    public function into($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['INTO'] . $stmt;
            return $this;
        }
        if (is_array($params[0])) {
            if (count($params) === 1) {
                $params = $params[0];
                //  detect the data type of the key for the first value,
                //      if the key is a string, then we have 'col' => 'values' pairs
                if (is_string(key($params))) {
                    $cols   =   null;
                    $values =   null;
                    foreach ($params as $col => $value) {
                        if ($col[0] === '@') {
                            $cols[]     =   substr($col, 1);
                            $values[]   =   $value;
                        } elseif (is_numeric($value) && (is_int($value) || is_float($value) || (string) $value === (string) (float) $value)) {
                            $cols[]     =   $col;
                            $values[]   =   $value;
                        } elseif (is_string($value)) {
                            $cols[]     =   $col;
                            $values[]   =   self::quote($value);
                        } elseif ($value === null) {
                            $cols[]     =   $col;
                            $values[]   =   'NULL';
                        } else {
                            throw new \BadMethodCallException('Invalid type `' . gettype($value) .
                                '` sent to SQL()->INTO("' . $stmt . '", ...) statement; only numeric, string and null values are supported!');
                        }
                    }
                    $params = $cols;
                } else {
                    foreach ($params as $index => $col) {
                        if ($col[0] === '@') {        //  strip '@' from beginning of all column names ... just in-case!
                            $params[$index] = substr($col, 1);
                        }
                    }
                }
            } elseif (is_array($params[1])) {
                if (count($params) !== 2) {
                    throw new \Exception('When the first two parameters supplied to SQL()->INTO("' . $stmt .
                            '", ...) statements are arrays, no other parameters are necessary!');
                }
                $cols   =   $params[0];
                $values =   $params[1];
                if (count($cols) !== count($values)) {
                    throw new \Exception('Mismatching number of columns and values: count of $columns array = ' .
                            count($cols) . ' and count of $values array = ' . count($values) .
                            ' (' . count($cols) . ' vs ' . count($values) . ') supplied to SQL()->INTO("' . $stmt . '", ...) statement');
                }
                foreach ($cols as $index => $col) {
                    if ($col[0] === '@') {
                        $cols[$index]   =   substr($col, 1);
                    //  $values[$index] =   $value[$index];     //  unchanged
                    } else {
                        $value = $values[$index];
                        if (is_numeric($value) && (is_int($value) || is_float($value) || (string) $value === (string) (float) $value)) {
                        //  $cols[$index]   =   $col;           //  unchanged
                        //  $values[$index] =   $value[$index]; //  unchanged
                        } elseif (is_string($value)) {
                        //  $cols[$index]   =   $col;           //  unchanged
                            $values[$index] =   self::quote($value);
                        } elseif ($value === null) {
                        //  $cols[$index]   =   $col;           //  unchanged
                            $values[$index] =   'NULL';
                        } else {
                            throw new \Exception('Invalid type `' . gettype($value) .
                                '` sent to SQL()->INTO("' . $stmt . '", ...) statement; only numeric, string and null values are supported!');
                        }
                    }
                }
                $params = $cols;
            } else {   //  syntax: INTO('users', ['col1', 'col2', '@dated'], $value1, $value2, 'CURDATE()')
                $cols   =   array_shift($params);   //  `Shift an element off the beginning of array`
                $values =   $params;
                if (count($cols) !== count($values)) {
                    throw new \Exception('Mismatching number of columns and values: count of $columns array = ' .
                            count($cols) . ' and count of $values = ' . count($values) .
                            ' (' . count($cols) . ' vs ' . count($values) . ') supplied to SQL()->INTO("' . $stmt . '", ...) statement');
                }
                foreach ($cols as $index => $col) {
                    if ($col[0] === '@') {
                        $cols[$index]   =   substr($col, 1);
                    //  $values[$index] =   $value[$index];     //  unchanged
                    } else {
                        $value = $values[$index];
                        if (is_numeric($value) && (is_int($value) || is_float($value) || (string) $value === (string) (float) $value)) {
                        //  $cols[$index]   =   $col;           //  unchanged
                        //  $values[$index] =   $value[$index]; //  unchanged
                        } elseif (is_string($value)) {
                        //  $cols[$index]   =   $col;           //  unchanged
                            $values[$index] =   self::quote($value);
                        } elseif ($value === null) {
                        //  $cols[$index]   =   $col;           //  unchanged
                            $values[$index] =   'NULL';
                        } else {
                            throw new \Exception('Invalid type `' . gettype($value) .
                                '` sent to SQL()->INTO("' . $stmt . '", ...) statement; only numeric, string and null values are supported!');
                        }
                    }
                }
                $params = $cols;
            }
            /*
            else
            {
                if (count($params) > 2) {
                    throw new \BadMethodCallException('Invalid number of parameters (' . count($params) .
                        ') supplied to SQL()->INTO() statement, when the first parameter is an array,
                        you can only supply One or Two arrays as params; One array with column name-value pairs
                        or Two arrays with column and values in each.');
                }
                throw new \BadMethodCallException('Invalid parameters (' . count($params) .
                    ') supplied to SQL()->INTO() statement. Please check the number of `?` and `@` values in the pattern; possibly requiring ' .
                    (   substr_count($pattern, '?') + substr_count($pattern, '@') -
                        substr_count($pattern, '??') - substr_count($pattern, '@@') -
                        substr_count($pattern, '\\?') - substr_count($pattern, '\\@') -
                    count($params)) . ' more value(s)');
            }
            */
        //  $this->sql .= 'INTO ' . $stmt .
        //                  ( ! empty($params)  ?   ' (' . implode(', ', $params) . ')' : null) .
        //                  ( ! empty($values)  ?   ' VALUES (' . implode(', ', $values) . ')' : null);
            $this->sql .= 'INTO ' . $stmt . ' (' . implode(', ', $params) . ') ' . (isset($values) ? 'VALUES (' . implode(', ', $values) . ')' : null);
            return $this;
        }
        //  syntax: ->INTO('users (col1, col2, dated) VALUES (?, ?, @)', $value1, $value2, 'CURDATE()')
        return $this->prepare('INTO ' . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                                VALUES                                **/
    /**************************************************************************/


    /**
     *  Generates an SQL `VALUES` statement
     *
     *  Example:
     *
     *      ->insertInto('users', ['id', 'name', 'created'])
     *      ->values('?, ?, @', 5, 'Trevor', 'NOW()');
     *
     *  Output:
     *
     *      INSERT INTO users (id, name, created) VALUES (5, "Trevor", NOW())
     *
     *  MySQL INSERT INTO Syntax:   {@link https://dev.mysql.com/doc/refman/5.7/en/insert.html}
     *
     *      INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name [PARTITION (partition_name,...)] [(col_name,...)]  {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
     *
     *  PostgreSQL INSERT INTO Syntax:  {@link https://www.postgresql.org/docs/8.2/static/sql-insert.html}
     *
     *      INSERT INTO table [ ( column [, ...] ) ] { DEFAULT VALUES | VALUES ( { expression | DEFAULT } [, ...] ) [, ...] | query } [ RETURNING * | output_expression [ AS output_name ] [, ...] ]
     *
     *  ANY array key starting with '@' will cause the value to NOT be escaped!
     *  eg. values(['value1', '@' => 'UNIX_TIMESTAMP()', '@1' => 'MAX(table)', '@2' => 'DEFAULT', '@3' => 'NULL'])
     *  eg. values('?, @, @', 'value1', 'DEFAULT', 'NULL')
     *  eg. values('5, 6, 7, 8, @id, CURDATE()')
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function values($stmt = null, ...$params)
    {
        if (empty($params)) {
            if (is_array($stmt)) {
                $values = '';
                $comma = null;
                foreach ($stmt as $col => $value) {
                    if (is_numeric($value) && (is_int($value) || is_float($value) || (string) $value === (string) (float) $value)) {
                        $values .= $comma . $value;
                    } elseif (is_string($value)) {
                        if (is_string($col) && $col[0] === '@') {     //  detect `raw output` modifier in column key/index/name!
                            $values .= $comma . $value;
                        } else {
                            $values .= $comma . self::quote($value);
                        }
                    } elseif ($value === null) {
                        $values .= $comma . 'NULL';
                    } else {
                        throw new \Exception('Invalid type `' . gettype($value) .
                            '` sent to VALUES([..]); only numeric, string and null are supported!');
                    }
                    $comma = ', ';
                }
            } else {
                $values = $stmt;
            }
            $this->sql .= ' VALUES (' . $values . ')';
            return $this;
        }
        return $this->prepare(' VALUES (' . $stmt . ')', ...$params);
    }


    /**
     *  Generates an SQL `VALUES` statement - shorthand for `values()`
     *
     *  @alias values()
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function v($stmt = null, ...$params)
    {
        if (empty($params)) {
            if (is_array($stmt)) {
                $values = '';
                $comma = null;
                foreach ($stmt as $col => $value) {
                    if (is_numeric($value) && (is_int($value) || is_float($value) || (string) $value === (string) (float) $value)) {
                        $values .= $comma . $value;
                    } elseif (is_string($value)) {
                        if (is_string($col) && $col[0] === '@') {     //  detect `raw output` modifier in column key/index/name!
                            $values .= $comma . $value;
                        } else {
                            $values .= $comma . self::quote($value);
                        }
                    } elseif ($value === null) {
                        $values .= $comma . 'NULL';
                    } else {
                        throw new \Exception('Invalid type `' . gettype($value) .
                            '` sent to VALUES([..]); only numeric, string and null are supported!');
                    }
                    $comma = ', ';
                }
            } else {
                $values = $stmt;
            }
            $this->sql .= ' VALUES (' . $values . ')';
            return $this;
        }
        return $this->prepare(' VALUES (' . $stmt . ')', ...$params);
    }


    /**************************************************************************/
    /**                                  SET                                 **/
    /**************************************************************************/


    /**
     *  Generates an SQL `SET` statement
     *
     *  @todo fix up this documentation
     *
     *  Samples:
     *  https://dev.mysql.com/doc/refman/5.7/en/insert.html
     *  https://dev.mysql.com/doc/refman/5.7/en/update.html
     *      INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE] [INTO] tbl_name SET col_name={expr | DEFAULT}, ... [ ON DUPLICATE KEY UPDATE col_name=expr [, col_name=expr] ... ]
     *      UPDATE [LOW_PRIORITY] [IGNORE] table_reference SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}]
     *
     *       ... ${id} || $id (looks too much like a variable!  #{id}  :{id}   @user   (entity framework!)  {0} = parameters by index!
     *
     *  Alternative 1: (['col1' => $value1, 'col2' => $value2, '@dated' => 'CURDATE()'])        single array:       [columns => values]
     *  Alternative 2: (['col1', 'col2', '@dated'], [$value1, $value2, 'CURDATE()'])            two arrays:         [columns], [values]
     *  Alternative 3: ('col1 = ?, col2 = ?, dated = @', $value1, $value2, 'CURDATE()')
     *  Alternative 4: (['col1 = ?', col2 = ?, dated = @', $value1, $value2, 'CURDATE()')   single array v2:    ['column', $value, 'column', $value]
     *
     *  @param  mixed       ...$args
     *
     *  @return $this
     */
    public function set(...$args)
    {
        $values = null;
        $comma = null;
        if (count($args) === 1 && is_array($args[0])) {
            foreach ($args[0] as $col => $value) {
                if ($col[0] === '@') {                        //  detect first character of column title ... if the title has '@' sign, then DO NOT ESCAPE! ... can be useful for 'DEFAULT', or '@id' or 'MD5(...)' etc. (a connection variable) etc.
                    $values .= $comma . substr($col, 1) . ' = ' . $value;       //  strip '@' from beginning of column
                } else {
                    if (is_numeric($value) && (is_int($value) || is_float($value) || (string) $value === (string) (float) $value)) {
                        $values .= $comma . $col . ' = ' . $value;
                    } elseif (is_string($value)) {
                        $values .= $comma . $col . ' = ' . $this->quote($value);
                    } elseif ($value === null) {
                        $values .= $comma . $col . ' = NULL';
                    } else {
                        throw new \Exception('Invalid type `' . gettype($value) . '` sent to SET(); only numeric, string and null are supported!');
                    }
                }
                $comma = ', ';
            }
        } else {
            $col = null;
            foreach ($args as $arg) {
                if ($col === null) {
                    $col = $arg;
                    if (empty($col) || is_numeric($col)) {    //    basic validation ... something is wrong ... can't have a column title be empty or numeric!
                        throw new \Exception('Invalid column name detected in SET(), column names must be strings! Type: `' . gettype($col) . '`, value: ' . (string) $col);
                    }
                    continue;
                }

                if ($col[0] === '@') {                        //  detect first character of column title ... if the title has '@' sign, then DO NOT ESCAPE! ... can be useful for 'DEFAULT', or '@id' (a connection variable) or 'MD5(...)' etc.
                    $values .= $comma . substr($col, 1) . ' = ' . $value;       //  strip '@' from beginning
                } else {
                    if (is_numeric($arg) && (is_int($arg) || is_float($arg) || (string) $arg === (string) (float) $arg)) {
                        $values .= $comma . $col . ' = ' . $arg;
                    } elseif (is_string($arg)) {
                        $values .= $comma . $col . ' = ' . $this->quote($arg);
                    } elseif ($arg === null) {
                        $values .= $comma . $col . ' = NULL';
                    } else {
                        throw new \Exception('Invalid type `' . gettype($arg) . '` sent to SET(); only numeric, string and null are supported!');
                    }
                }
                $comma = ', ';
                $col = null;
            }
        }
        $this->sql .= ' SET ' . $values;
        return $this;
    }


    /**************************************************************************/
    /**                             DELETE                                   **/
    /**************************************************************************/


    /**
     *  Generates an SQL `DELETE` statement
     *
     *  See {@see prepare()} for optional syntax rules
     *
     *  eg. `->delete('FROM users ...', ...)`
     *      `DELETE FROM users ...`
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function delete($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['DELETE'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['DELETE'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `DELETE` statement - shorthand for `delete()`
     *
     *  @alias delete()
     *
     *  eg. `->d('FROM users ...', ...)`
     *      `DELETE FROM users ...`
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function d($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['DELETE'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['DELETE'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                          DELETE FROM                                 **/
    /**************************************************************************/


    /**
     *  Generates an SQL `DELETE FROM` statement
     *
     *  See {@see delete_from()} for 'snake_case' alternative
     *
     *  eg. `->deleteFrom('users ...', ...)`
     *      `DELETE FROM users ...`
     *
     *  See {@see prepare()} for optional syntax rules
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function deleteFrom($stmt, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['DELETE_FROM'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['DELETE_FROM'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `DELETE FROM` statement
     *
     *  See {@see deleteFrom()} for 'camelCase' alternative
     *
     *  eg. `->delete_from('users ...', ...)`
     *      `DELETE FROM users ...`
     *
     *  See {@see prepare()} for optional syntax rules
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function delete_from($stmt, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['DELETE_FROM'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['DELETE_FROM'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `DELETE FROM` statement - shorthand for `deleteFrom()`
     *
     *  This is exactly the same as calling `deleteFrom()` or `delete_from()`
     *  just conveniently shorter syntax
     *
     *  eg. `->df('users ...', ...)`
     *      `DELETE FROM users ...`
     *
     *  @alias delete_from()
     *  @alias deleteFrom()
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function df($stmt, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['DELETE_FROM'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['DELETE_FROM'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                             UPDATE                                   **/
    /**************************************************************************/


    /**
     *  Generates an SQL `UPDATE` statement
     *
     *  See {@see prepare()} for optional syntax rules
     *
     *  eg. `->update('users ...', ...)`
     *      `UPDATE users ...`
     *
     *  {@link https://dev.mysql.com/doc/refman/5.7/en/update.html}
     *
     *  MySQL syntax:
     *
     *      UPDATE [LOW_PRIORITY] [IGNORE] table_reference
     *          SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}] ...
     *          [WHERE where_condition]
     *          [ORDER BY ...]
     *          [LIMIT row_count]
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function update($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['UPDATE'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['UPDATE'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `UPDATE` statement - shorthand for `update()`
     *
     *  @alias update()
     *
     *  eg. `->u('FROM users ...', ...)`
     *      `UPDATE users ...`
     *
     *  {@link https://dev.mysql.com/doc/refman/5.7/en/update.html}
     *
     *  MySQL syntax:
     *
     *      UPDATE [LOW_PRIORITY] [IGNORE] table_reference
     *          SET col_name1={expr1|DEFAULT} [, col_name2={expr2|DEFAULT}] ...
     *          [WHERE where_condition]
     *          [ORDER BY ...]
     *          [LIMIT row_count]
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function u($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['UPDATE'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['UPDATE'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                                EXPLAIN                               **/
    /**************************************************************************/


    /**
     *  Generates an SQL `EXPLAIN` statement
     *
     *  Might be MySQL specific
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function explain($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql = self::$translations['EXPLAIN'] . $this->sql;
            return $this;
        }
        return $this->prepare(self::$translations['EXPLAIN'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                                SELECT                                **/
    /**************************************************************************/


    /**
     *  Generates an SQL 'SELECT' statement
     *
     *  This function will join/implode a list of columns/fields
     *
     *  eg. `$sql = sql()->select('u.id', 'u.name', 'u.foo', 'u.bar');
     *
     *  Due to the greater convenience provided by this method,
     *      the `prepare()` syntax is not provided here
     *
     *  `prepare()`/`sprintf()` like functionality can be provided by using
     *      another Sql Query Object's constructor like this:
     *
     *  `$sql = sql()->select('u.id',
     *                        // note how the next `sql()` call will be converted to a string
     *                        sql('(SELECT ... WHERE a.id = @) AS foo', $id),
     *                        'u.name')
     *               ->from('users u');`
     *
     *  @param  string ...$cols Column list will be imploded with ', '
     *
     *  @return $this
     */
    public function select(...$cols)
    {
        $this->sql .= self::$translations['SELECT'] . implode(', ', $cols);
        return $this;
    }


    /**
     *  Generates an SQL 'SELECT' statement
     *
     *  This function will join/implode a list of columns/fields
     *
     *  eg. `->s('u.id', 'u.name', 'u.foo', 'u.bar');
     *
     *  Due to the greater convenience provided by this method,
     *      the `prepare()` syntax is not provided here
     *
     *  `prepare()`/`sprintf()` like functionality can be provided by using
     *      another Sql Query Object's constructor like this:
     *
     *  `$sql = sql()->s('u.id',
     *                        // note how the next `sql()` call will be converted to a string
     *                        sql('(SELECT ... WHERE a.id = @) AS foo', $id),
     *                        'u.name')
     *               ->f('users u');`
     *
     *  @param  string ...$cols Column list will be imploded with ', '
     *
     *  @return $this
     */
    public function s(...$cols)
    {
        $this->sql .= self::$translations['SELECT'] . implode(', ', $cols);
        return $this;
    }


    /**
     *  Generates an SQL 'SELECT DISTINCT' statement
     *
     *  This function will join/implode a list of columns/fields
     *
     *  eg. `$sql = sql()->selectDistinct('u.id', 'u.name', 'u.foo', 'u.bar');
     *
     *  @param  string ...$cols Column list will be imploded with ', '
     *
     *  @return $this
     */
    public function selectDistinct(...$cols)
    {
        $this->sql .= self::$translations['SELECT'] . self::$translations['DISTINCT'] . implode(', ', $cols);
        return $this;
    }


    /**
     *  Generates an SQL 'SELECT DISTINCT' statement
     *
     *  This function will join/implode a list of columns/fields
     *
     *  @alias selectDistinct()
     *
     *  This function is the 'snake case' alias of selectDistinct()
     *
     *  Examples:
     *
     *      `->select_distinct('u.id', 'u.name', 'u.foo', 'u.bar');
     *      `->SELECT_DISTINCT('u.id', 'u.name', 'u.foo', 'u.bar');
     *
     *  @param  string ...$cols Column list will be imploded with ', '
     *
     *  @return $this
     */
    public function select_distinct(...$cols)
    {
        $this->sql .= self::$translations['SELECT'] . self::$translations['DISTINCT'] . implode(', ', $cols);
        return $this;
    }


    /**
     *  Generates an SQL 'SELECT DISTINCT' statement
     *
     *  This function will join/implode a list of columns/fields
     *
     *  @alias selectDistinct()
     *  @alias select_distinct()
     *
     *  This function is the short syntax version of selectDistinct()
     *
     *  Examples:
     *
     *      `->sd('u.id', 'u.name', 'u.foo', 'u.bar');
     *      `->sd('u.id', 'u.name', 'u.foo', 'u.bar');
     *
     *  @param  string ...$cols Column list will be imploded with ', '
     *
     *  @return $this
     */
    public function sd(...$cols)
    {
        $this->sql .= self::$translations['SELECT'] . self::$translations['DISTINCT'] . implode(', ', $cols);
        return $this;
    }


    /**
     *  Generates an SQL `SELECT $modifier ...` statement
     *
     *  Adds a custom `SELECT` modifier such as DISTINCT, SQL_CACHE etc.
     *
     *  See {@see prepare()} for optional syntax rules
     *
     *  @param  string      $modifier
     *                      An SQL `SELECT` statement modifier to place after the `SELECT`
     *                      statement; such as `DISTINCT` or `SQL_CACHE`
     *
     *  @param  string      ...$cols Column list will be imploded with ', '
     *
     *  @return $this
     */
    public function selectWithModifier($modifier, ...$cols)
    {
        $this->sql .= self::$translations['SELECT'] . $modifier . ' ' . implode(', ', $cols);
        return $this;
    }


    /**************************************************************************/
    /**                                 FROM                                 **/
    /**************************************************************************/


    /**
     *  Generates an SQL `FROM` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function from($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['FROM'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['FROM'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `FROM` statement - shorthand for `from()`
     *
     *  @alias from()
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function f($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['FROM'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['FROM'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                                 JOIN                                 **/
    /**************************************************************************/


    /**
     *  Generates an SQL `JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function join($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `JOIN` statement - shorthand for `join()`
     *
     *  @alias join()
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function j($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `JOIN $table ON` statement
     *
     *  Combines functionality of JOIN and ON ... experimental!
     *
     *  @param  string      $table
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function join_on($table, $stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['JOIN'] . $table . self::$translations['ON'];
            return $this;
        }
        return $this->prepare(self::$translations['JOIN'] . $table . self::$translations['ON'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `JOIN $table ON` statement - shorthand for `join_on()`
     *
     *  @alias join_on()
     *
     *  @param  string      $table
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function j_on($table, $stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['JOIN'] . $table . self::$translations['ON'];
            return $this;
        }
        return $this->prepare(self::$translations['JOIN'] . $table . self::$translations['ON'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `JOIN $table ON` statement
     *
     *  Alternative spelling for `join_on`
     *
     *  @param  string      $table
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function joinOn($table, $stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['JOIN'] . $table . self::$translations['ON'];
            return $this;
        }
        return $this->prepare(self::$translations['JOIN'] . $table . self::$translations['ON'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `JOIN $table ON` statement - shorthand for `joinOn()`
     *
     *  @alias joinOn()
     *
     *  @param  string      $table
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function jOn($table, $stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['JOIN'] . $table . self::$translations['ON'];
            return $this;
        }
        return $this->prepare(self::$translations['JOIN'] . $table . self::$translations['ON'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                              LEFT JOIN                               **/
    /**************************************************************************/


    /**
     *  Generates an SQL `LEFT JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function left_join($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['LEFT_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['LEFT_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `LEFT JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function leftJoin($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['LEFT_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['LEFT_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `LEFT JOIN` statement
     *
     *  @alias left_join()
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function lj($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['LEFT_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['LEFT_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `LEFT OUTER JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function left_outer_join($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['LEFT_OUTER_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['LEFT_OUTER_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `LEFT OUTER JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function leftOuterJoin($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['LEFT_OUTER_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['LEFT_OUTER_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `LEFT OUTER JOIN` statement
     *
     *  @alias left_outer_join()
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function loj($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['LEFT_OUTER_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['LEFT_OUTER_JOIN'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                              RIGHT JOIN                              **/
    /**************************************************************************/


    /**
     *  Generates an SQL `RIGHT JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function right_join($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['RIGHT_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['RIGHT_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `RIGHT JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function rightJoin($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['RIGHT_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['RIGHT_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `RIGHT JOIN` statement
     *
     *  @alias right_join()
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function rj($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['RIGHT_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['RIGHT_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `RIGHT OUTER JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function right_outer_join($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['RIGHT_OUTER_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['RIGHT_OUTER_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `RIGHT OUTER JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function rightOuterJoin($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['RIGHT_OUTER_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['RIGHT_OUTER_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `RIGHT OUTER JOIN` statement
     *
     *  @alias right_outer_join()
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function roj($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['RIGHT_OUTER_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['RIGHT_OUTER_JOIN'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                              INNER JOIN                              **/
    /**************************************************************************/


    /**
     *  Generates an SQL `INNER JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function inner_join($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['INNER_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['INNER_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `INNER JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function innerJoin($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['INNER_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['INNER_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `INNER JOIN` statement
     *
     *  @alias inner_join()
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function ij($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['INNER_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['INNER_JOIN'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                              OUTER JOIN                              **/
    /**************************************************************************/


    /**
     *  Generates an SQL `OUTER JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function outer_join($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['OUTER_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['OUTER_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `OUTER JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function outerJoin($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['OUTER_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['OUTER_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `OUTER JOIN` statement
     *
     *  @alias outer_join()
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function oj($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['OUTER_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['OUTER_JOIN'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                              CROSS JOIN                              **/
    /**************************************************************************/


    /**
     *  Generates an SQL `CROSS JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function cross_join($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['CROSS_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['CROSS_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `CROSS JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function crossJoin($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['CROSS_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['CROSS_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `CROSS JOIN` statement
     *
     *  @alias cross_join()
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function cj($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['CROSS_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['CROSS_JOIN'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                             STRAIGHT_JOIN                            **/
    /**************************************************************************/


    /**
     *  Generates an SQL `STRAIGHT_JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function straight_join($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['STRAIGHT_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['STRAIGHT_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `STRAIGHT_JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function straightJoin($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['STRAIGHT_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['STRAIGHT_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `STRAIGHT_JOIN` statement
     *
     *  @alias straight_join()
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function sj($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['STRAIGHT_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['STRAIGHT_JOIN'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                             NATURAL JOIN                             **/
    /**************************************************************************/


    /**
     *  Generates an SQL `NATURAL JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function natural_join($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['NATURAL_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['NATURAL_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `NATURAL JOIN` statement
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function naturalJoin($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['NATURAL_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['NATURAL_JOIN'] . $stmt, ...$params);
    }


    /**
     *  Generates an SQL `NATURAL JOIN` statement
     *
     *  @alias natural_join()
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function nj($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['NATURAL_JOIN'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['NATURAL_JOIN'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                                USING                                 **/
    /**************************************************************************/


    /**
     *  Generates an SQL `USING` statement
     *
     *  $fields list is joined/imploded with `', '`
     *  $fields are NOT escaped or quoted
     *
     *  Example:
     *
     *      echo sql()->using('id', 'acc');
     *       USING (id, acc)
     *
     *  @param  string ...$fields
     *
     *  @return $this
     */
    public function using(...$fields)
    {
        $this->sql .= self::$translations['USING'] . '(' . implode(', ', $fields) . ')';
        return $this;
    }


    /**************************************************************************/
    /**                                ON                                    **/
    /**************************************************************************/


    /**
     *  Generates an SQL `ON` statement
     *
     *  Generates an `ON` statement with convenient `prepare()` syntax (optional)
     *
     *  See {@see \Sql::prepare()} for optional syntax rules
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function on($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['ON'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['ON'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                                UNION                                 **/
    /**************************************************************************/


    /**
     *  Generates an SQL `UNION` statement
     *
     *  Generates a `UNION` statement with convenient `prepare()` syntax (optional)
     *
     *  Example:
     *
     *      ->union()
     *
     *      ->union('SELECT * FROM users')
     *
     *      ->union()
     *          ->select('* FROM users')
     *
     *      ->union()
     *          ->select('*')
     *          ->from('users') ...
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function union($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['UNION'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['UNION'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                                WHERE                                 **/
    /**************************************************************************/


    /**
     *  Generates an SQL `WHERE` statement
     *
     *  Generates a `WHERE` statement with convenient `prepare()` syntax (optional)
     *
     *  See {@see \Sql::prepare()} for optional syntax rules
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function where($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['WHERE'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['WHERE'] . $stmt, ...$params);
    }


    /**
     *  Generate an SQL `WHERE` statement - shorthand for `where()`
     *
     *  Generate a `WHERE` statement with convenient `prepare()` syntax (optional)
     *
     *  This is the same as `where()`, only shorthand form
     *
     *  @alias where()
     *
     *  See {@see \Sql::prepare()} for optional syntax rules
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       $params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function w($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['WHERE'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['WHERE'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                                 IN                                   **/
    /**************************************************************************/


    /**
     *  Generates an SQL `IN` statement
     *
     *  Automatically determines $args member data types
     *  Automatically quotes and escapes strings
     *
     *  Essentially provides the same service as implode()
     *  But with the added benefit of intelligent escaping and quoting
     *  However, implode() will be more efficient for numeric arrays
     *
     *  Example:
     *
     *      `->in([1, 2, 3])`
     *      ` IN (1, 2, 3)`
     *
     *      `->in('abc', 'def', $var = 'ghi')`
     *      ` IN ("abc", "def", "ghi")`
     *
     *  Samples:
     *      DELETE FROM t WHERE i IN(1, 2);
     *
     *  @param  mixed       ...$args
     *
     *  @return $this
     */
    public function in(...$args)
    {
        $comma = null;
        $this->sql .= ' IN (';
        if (count($args) && is_array($args[0])) {
            $args = $args[0];
        }
        foreach ($args as $arg) {
            if (is_numeric($arg) && (is_int($arg) || is_float($arg) || (string) $arg === (string) (float) $arg)) {
                $this->sql .= $comma . $arg;
            } elseif (is_string($arg)) {
                $this->sql .= $comma . self::quote($arg);
            } elseif (is_null($arg)) {
                $this->sql .= $comma . 'NULL';
            } elseif (is_bool($arg)) {
                $this->sql .= $comma . $arg ? '1' : '0';
            } else {
                throw new \InvalidArgumentException('Invalid data type `' . (is_object($arg) ? get_class($arg) : gettype($arg)) .
                            '` given to Sql->in(), only scalar (int, float, string, bool), NULL and arrays are allowed!');
            }
            $comma = ', ';
        }
        $this->sql .= ')';
        return $this;
    }


    /**************************************************************************/
    /**                               GROUP BY                               **/
    /**************************************************************************/


    /**
     *  Generates an SQL `GROUP BY` statement
     *
     *  Example:
     *
     *      `->groupBy('dated, name')`
     *  or
     *      `->groupBy('dated', 'name')`
     *
     *  Output:
     *
     *      `GROUP BY (dated, name)`
     *
     *  @param  string       ...$cols
     *
     *  @return $this
     */
    public function groupBy(...$cols)
    {
        $this->sql .= self::$translations['GROUP_BY'] . implode(', ', $cols);
        return $this;
    }


    /**
     *  Generates an SQL `GROUP BY` statement
     *
     *  @alias groupBy()
     *
     *  This is the 'snake case' equivalent of groupBy()
     *  Can also be used in ALL CAPS eg. `->GROUP_BY(...)`
     *
     *  Example:
     *
     *      `->group_by('dated, name')`
     *      `->GROUP_BY('dated, name')`
     *
     *  or
     *
     *      `->group_by('dated', 'name')`
     *      `->GROUP_BY('dated', 'name')`
     *
     *  Output:
     *
     *      `GROUP BY (dated, name)`
     *
     *  @param  string       ...$cols
     *
     *  @return $this
     */
    public function group_by(...$cols)
    {
        $this->sql .= self::$translations['GROUP_BY'] . implode(', ', $cols);
        return $this;
    }


    /**
     *  Generates an SQL `GROUP BY` statement
     *
     *  @alias groupBy()
     *
     *  This is the shorthand equivalent of `groupBy()` and `group_by()` for convenience!
     *
     *  Example:
     *
     *      `->gb('dated, name')`
     *      `GROUP BY (dated, name)`
     *
     *  @param  string       ...$cols
     *
     *  @return $this
     */
    public function gb(...$cols)
    {
        $this->sql .= self::$translations['GROUP_BY'] . implode(', ', $cols);
        return $this;
    }


    /**************************************************************************/
    /**                                HAVING                                 **/
    /**************************************************************************/


    /**
     *  Generates an SQL `HAVING` statement
     *
     *  Generates a `HAVING` statement with convenient `prepare()` syntax (optional)
     *
     *  See {@see \Sql::prepare()} for optional syntax rules
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       ...$params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function having($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['HAVING'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['HAVING'] . $stmt, ...$params);
    }


    /**
     *  Generate an SQL `HAVING` statement - shorthand for `having()`
     *
     *  Generate a `HAVING` statement with convenient `prepare()` syntax (optional)
     *
     *  This is the same as `having()`, only shorthand form
     *
     *  @alias having()
     *
     *  See {@see \Sql::prepare()} for optional syntax rules
     *
     *  @param  string|null $stmt
     *                      (optional) Statement to `prepare()`;
     *
     *  @param  mixed       $params
     *                      (optional) Parameters associated with $stmt
     *
     *  @return $this
     */
    public function h($stmt = null, ...$params)
    {
        if (empty($params)) {
            $this->sql .= self::$translations['HAVING'] . $stmt;
            return $this;
        }
        return $this->prepare(self::$translations['HAVING'] . $stmt, ...$params);
    }


    /**************************************************************************/
    /**                               ORDER BY                               **/
    /**************************************************************************/


    /**
     *  Generates an SQL `ORDER BY` statement
     *
     *  Example:
     *
     *      `->orderBy('dated DESC, name')`
     *  or
     *      `->orderBy('dated DESC', 'name')`
     *
     *  Output:
     *
     *      `ORDER BY (dated DESC, name)`
     *
     *  Also supports the following syntax:
     *
     *      `->orderBy('dated', 'DESC');`
     *
     *  @param  string       ...$cols
     *
     *  @return $this
     */
    public function orderBy(...$cols)
    {
        $this->sql .= self::$translations['ORDER_BY'];
        $comma = null;
        foreach ($cols as $arg) {
            if ($comma === null) {
                $this->sql .= $arg;
                $comma = ', ';
            } else {
                switch (trim(strtoupper($arg))) {
                    case 'DESC':
                    case 'ASC':
                        $this->sql .= ' ' . $arg;
                        break;
                    default:
                        $this->sql .= $comma . $arg;
                }
            }
        }
        return $this;
    }


    /**
     *  Generates an SQL `ORDER BY` statement
     *
     *  @alias orderBy()
     *
     *  This is the 'snake case' equivalent of orderBy()
     *  Can also be used in ALL CAPS eg. `->ORDER_BY(...)`
     *
     *  Example:
     *
     *      `->order_by('dated DESC, name')`
     *      `->ORDER_BY('dated DESC, name')`
     *
     *  or
     *
     *      `->order_by('dated DESC', 'name')`
     *      `->ORDER_BY('dated DESC', 'name')`
     *
     *  Output:
     *
     *      `ORDER BY (dated DESC, name)`
     *
     *  Also supports the following syntax:
     *
     *      `->order_by('dated', 'DESC');`
     *
     *  @param  string       ...$cols
     *
     *  @return $this
     */
    public function order_by(...$cols)
    {
        $this->sql .= self::$translations['ORDER_BY'];
        $comma = null;
        foreach ($cols as $arg) {
            if ($comma === null) {
                $this->sql .= $arg;
                $comma = ', ';
            } else {
                switch (trim(strtoupper($arg))) {
                    case 'DESC':
                    case 'ASC':
                        $this->sql .= ' ' . $arg;
                        break;
                    default:
                        $this->sql .= $comma . $arg;
                }
            }
        }
        return $this;
    }


    /**
     *  Generates an SQL `ORDER BY` statement
     *
     *  @alias orderBy()
     *
     *  This is the shorthand equivalent of `orderBy()` and `order_by()` for convenience!
     *
     *  Example:
     *
     *      `->ob('dated DESC', 'name')`
     *      `ORDER BY (dated DESC, name)`
     *
     *  Also supports the following syntax:
     *
     *      `sql()->ob('dated', 'DESC');`
     *
     *  @param  string       ...$cols
     *
     *  @return $this
     */
    public function ob(...$cols)
    {
        $this->sql .= self::$translations['ORDER_BY'];
        $comma = null;
        foreach ($cols as $arg) {
            if ($comma === null) {
                $this->sql .= $arg;
                $comma = ', ';
            } else {
                switch (trim(strtoupper($arg))) {
                    case 'DESC':
                    case 'ASC':
                        $this->sql .= ' ' . $arg;
                        break;
                    default:
                        $this->sql .= $comma . $arg;
                }
            }
        }
        return $this;
    }


    /**************************************************************************/
    /**                                 LIMIT                                **/
    /**************************************************************************/


    /**
     *  Generates an SQL `LIMIT` statement
     *
     *  LIMIT syntax has 2 variations:
     *      [LIMIT {[offset,] row_count | row_count OFFSET offset}]
     *      LIMIT 5
     *      LIMIT 5, 10
     *      LIMIT 10 OFFSET 5
     *
     *  Example:
     *      ->LIMIT(5)
     *      ->LIMIT(10, 5)
     *      ->LIMIT(5)->OFFSET(10)
     *
     *  @param  int       $v1
     *  @param  int       $v2
     *
     *  @return $this
     */
    public function limit($v1, $v2 = null)
    {
        $this->sql .= self::$translations['LIMIT'] . $v1 . ($v2 === null ? null : ', ' . $v2);
        return $this;
    }


    /**
     *  Generates an SQL `LIMIT` statement
     *
     *  This is the shorthand equivalent of `limit()` for convenience!
     *
     *  @alias limit()
     *
     *  LIMIT syntax has 2 variations:
     *      [LIMIT {[offset,] row_count | row_count OFFSET offset}]
     *      LIMIT 5
     *      LIMIT 5, 10
     *      LIMIT 10 OFFSET 5
     *
     *  Example:
     *      ->LIMIT(5)
     *      ->LIMIT(10, 5)
     *      ->LIMIT(5)->OFFSET(10)
     *
     *  @param  int       $v1
     *  @param  int       $v2
     *
     *  @return $this
     */
    public function l($v1, $v2 = null)
    {
        $this->sql .= self::$translations['LIMIT'] . $v1 . ($v2 === null ? null : ', ' . $v2);
        return $this;
    }


    /**
     *  Generates an (uncommon) SQL `OFFSET` statement
     *
     *  This generates an `OFFSET`, used in conjuntion with `LIMIT`
     *
     *  This statement has limited use/application because `LIMIT 5, 10`
     *  is more convenient and shorter than `LIMIT 10 OFFSET 5`
     *
     *  However, the shortened version might not be supported on all databases
     *
     *  LIMIT syntax has 2 variations:
     *      [LIMIT {[offset,] row_count | row_count OFFSET offset}]
     *      LIMIT 5
     *      LIMIT 10, 5
     *      LIMIT 5 OFFSET 10
     *
     *  Example:
     *      ->LIMIT(5)
     *      ->LIMIT(10, 5)
     *      ->LIMIT(5)->OFFSET(10)
     *
     *  Samples:
     *
     *  @param  int       $offset
     *
     *  @return $this
     */
    public function offset($offset)
    {
        $this->sql .= self::$translations['OFFSET'] . $offset;
        return $this;
    }


    /**************************************************************************/
    /**                              sprintf()                               **/
    /**************************************************************************/


    /**
     *  `sprintf()` wrapper
     *
     *  Wrapper for executing an `sprintf()` statement, and writing
     *      the result directly to the internal `$sql` string buffer
     *
     *  Warning: Values here are passed directly to `sprintf()` without any
     *      other escaping or quoting, it's a direct call!
     *
     *  @link   http://php.net/manual/en/function.sprintf.php
     *
     *  Example:
     *
     *      `->sprintf('SELECT * FROM users WHERE id = %d', $id)`
     *      `SELECT * FROM users WHERE id = 5`
     *
     *  @param  string       $format The format string is composed of zero or more directives
     *
     *  @param  string       ...$args
     *
     *  @return $this
     */
    public function sprintf($format, ...$args)
    {
        $this->sql .= sprintf($format, ...$args);
        return $this;
    }


    /**************************************************************************/
    /**                              clamp()                                 **/
    /**************************************************************************/


    /**
     *  Custom `clamp` function; clamps values between a $min and $max range
     *
     *  $value can also be a database field name
     *  All values are appended without quotes or escapes
     *  $min and $max can be database field names
     *
     *  Example:
     *
     *      ->clamp('price', $min, $max)
     *
     *  Samples:
     *      max($min, min($max, $current));
     *
     *  @param  int|string  $value  Value, column or field name
     *  @param  int|string  $min    Min value or field name
     *  @param  int|string  $max    Max value or field name
     *  @param  string|null $as     (optional) print an `AS` clause
     *
     *  @return $this
     */
    public function clamp($value, $min, $max, $as = null)
    {
        $this->sql .= 'MIN(MAX(' . $value . ', ' . $min . '), ' . $max . ')' . ($as === null ? null : ' AS ' . $as);
        return $this;
    }


    /**************************************************************************/
    /**                              prepare()                               **/
    /**************************************************************************/


    /**
     *  Prepare a given input string with given parameters
     *
     *  Prepares a statement for execution but write the result to the internal buffer
     *
     *  WARNING: This function doesn't replace the `PDO::prepare()` statement for security, only convenience!
     *
     *  @todo This is the central function, with constant work and room for improvements
     *
     *  @param string $stmt       Statement with zero or more directives
     *
     *  @param mixed  ...$params Values to replace and/or escape from statement
     *
     *  @return $this
     */
    public function prepare($stmt, ...$params)  //  \%('.+|[0 ]|)([1-9][0-9]*|)s        somebody else's sprintf('%s') multi-byte conversion ... %s includes the ability to add padding etc.
    {
        $count = 0;
        if (count($params) === 1 && is_array($params[0])) {       //    allows the following syntax (where there is only one param, and it's an array):
                                                               //       ->prepare('WHERE id IN (?, ?, ?)', [1, 2, 3])
            $params = $params[0];                               //  problem is when the first value is for :json_encode ... we can allow ONE decode ?
            $params_conversion = true;                          //  AKA compatibility mode - we need to know if we executed `compatibility mode` or not, one reason is to support :json_encode, when there is only ONE value passed, then $params become our value, and not $params[0]!
        }
        $this->sql .= mb_ereg_replace_callback(
            '\?\?|\\?|\\\%|%%|\\@|@@|(?:\?|\d+)\.\.(?:\?|\d+)|\[(.*?)\]|\?|@[^a-zA-Z]?|[%:]([a-zA-Z0-9][a-zA-Z0-9_-]*)(\:[a-z0-9\.\-:]*)*(\{[^\{\}]+\})?|%sn?(?::?\d+)?|%d|%u(?:\d+)?|%f|%h|%H|%x|%X',
            function ($matches) use (&$count, $stmt, &$params, &$params_conversion, &$keys) {
                        $match = $matches[0];
                switch ($match[0]) {
                    case '?':
                        if ($match === '??' || $match === '\\?') {
                            return '?';
                        }

                        $value = current($params);
                        if ($value === false && key($params) === null) {
                            throw new \BadMethodCallException('Invalid number of parameters (' . count($params) .
                                ') supplied to Sql->prepare(`' . $stmt .
                                '`) pattern! Please check the number of `?` and `@` values in the statement pattern; possibly requiring at least 1 or ' .
                                (   substr_count($stmt, '?') + substr_count($stmt, '@') -
                                    substr_count($stmt, '??') - substr_count($stmt, '@@') -
                                    substr_count($stmt, '\\?') - substr_count($stmt, '\\@') -
                                count($params)) . ' more value(s)');
                        }
                        next($params);
                        $count++;

                        if (is_numeric($value) && (is_int($value) || is_float($value) || (string) $value === (string) (float) $value)) {
                            return (string) $value;     //  first testing numeric here, so we can skip the quotes and escaping for '1'
                        }
                        if (is_string($value)) {
                            return self::quote($value);
                        }
                        if (is_null($value)) {
                            return 'NULL';
                        }
                        if (is_bool($value)) {
                            return $value ? '1' : '0';  //  bool values return '' when false
                        }
                        if (is_array($value)) {                             //  same code used in [?]
                            $comma = null;
                            $result = '';
                            foreach ($value as $v) {
                                if (is_numeric($v) && (is_int($v) || is_float($v) || (string) $v === (string) (float) $v)) {
                                    $result .= $comma . $v;
                                } elseif (is_string($v)) {
                                    $result .= $comma . self::quote($v);
                                } elseif (is_null($v)) {
                                    $result .= $comma . 'NULL';
                                } elseif (is_bool($v)) {
                                    $result .= $comma . $v ? '1' : '0';
                                } else {
                                    throw new \InvalidArgumentException('Invalid data type `' . (is_object($value) ? get_class($value) : gettype($value)) .
                                       '` given in array passed to Sql->prepare(`' . $stmt .
                                       '`) pattern, only scalar (int, float, string, bool) and NULL values are allowed in `?` statements!');
                                }
                                                $comma = ', ';
                            }
                            return $result;
                        }

                        if (prev($params) === false && key($params) === null) {
                            end($params); // backtrack for key
                        }
                        throw new \InvalidArgumentException('Invalid data type `' . (is_object($value) ? get_class($value) : gettype($value)) .
                                        '` given at index ' . key($params) . ' passed to Sql->prepare(`' . $stmt .
                                        '`) pattern, only scalar (int, float, string, bool), NULL and single dimension arrays are allowed in `?` statements!');

                    case '@':
                        if ($match === '@@' || $match === '\\@') {
                            return '@';
                        }

                        $value = current($params);
                        if ($value === false && key($params) === null) {
                            throw new \BadMethodCallException('Invalid number of parameters (' . count($params) .
                                ') supplied to Sql->prepare(`' . $stmt .
                                '`) pattern! Please check the number of `?` and `@` values in the pattern; possibly requiring ' .
                                (   substr_count($stmt, '?') + substr_count($stmt, '@') -
                                    substr_count($stmt, '??') - substr_count($stmt, '@@') -
                                    substr_count($stmt, '\\?') - substr_count($stmt, '\\@') -
                                count($params)) . ' more value(s)');
                        }
                        next($params);
                        $count++;

                        if (is_string($value)) {
                            return $value;  //  first test for a string because it's the most common case for @
                        }
                        if (is_numeric($value) && (is_int($value) || is_float($value) || (string) $value === (string) (float) $value)) {
                            return (string) $value;
                        }
                        if (is_null($value)) {
                            return 'NULL';
                        }
                        if (is_bool($value)) {
                            return $value ? '1' : '0';  //  bool values return '' when false
                        }
                        if (is_array($value)) {
                            return implode(', ', $value);   //  WARNING: This isn't testing NULL and bool!
                        }

                        if (prev($params) === false && key($params) === null) {
                            end($params); // backtrack for key
                        }
                        throw new \InvalidArgumentException('Invalid data type `' . (is_object($value) ? get_class($value) : gettype($value)) .
                                        '` given at index ' . key($params) . ' passed to Sql->prepare(`' . $stmt .
                                        '`) pattern, only scalar (int, float, string, bool), NULL and single dimension arrays are allowed in `@` (raw output) statements!');

                    case '[':
                        if (isset($params_conversion) && $params_conversion) {    //  the first $param[0] WAS an array (as tested at the top) ... and there was only one value ...
                            $array  =   $params;                                //  $params IS an array and IS our actual value, not the first value OF params!
                        } else {
                            $array = current($params);
                            if ($array === false && key($params) === null) {
                                throw new \BadMethodCallException('Invalid number of parameters (' . count($params) .
                                    ') supplied to Sql->prepare(`' . $stmt .
                                    '`) pattern! Please check the number of `?` and `@` values in the statement pattern; possibly requiring ' .
                                    (   substr_count($stmt, '?') + substr_count($stmt, '@') -
                                        substr_count($stmt, '??') - substr_count($stmt, '@@') -
                                        substr_count($stmt, '\\?') - substr_count($stmt, '\\@') -
                                            count($params)) . ' more value(s)');
                            }
                                    next($params);
                                    $count++;
                        }

                        if (! is_array($array)) {
                            if (prev($params) === false && key($params) === null) {
                                end($params); // backtrack for key
                            }
                            throw new \InvalidArgumentException('Invalid data type `' . (is_object($array) ? get_class($array) : gettype($array)) .
                                            '` given at index ' . key($params) . ' passed to Sql->prepare(`' . $stmt .
                                            '`) pattern, only arrays are allowed in `[]` statements!');
                        }

                        if ($match === '[]' || $match === '[@]') {
                            return implode(', ', $array);   //  WARNING: This isn't testing NULL and bool!
                        } elseif ($match === '[?]') {  //   same thing as `?` ... why use/support this? I guess because it's more explicit !?!? ... going to deprecate ? as an array placeholder!
                        //if (is_array($array)) {       //  same code as `?`
                                $comma = null;
                                $result = '';
                            foreach ($array as $v) {
                                if (is_numeric($v) && (is_int($v) || is_float($v) || (string) $v === (string) (float) $v)) {
                                    $result .= $comma . $v;
                                } elseif (is_string($v)) {
                                    $result .= $comma . self::quote($v);
                                } elseif (is_null($v)) {
                                    $result .= $comma . 'NULL';
                                } elseif (is_bool($v)) {
                                    $result .= $comma . $v ? '1' : '0';
                                } else {
                                    throw new \InvalidArgumentException('Invalid data type `' . (is_object($array) ? get_class($array) : gettype($array)) .
                                            '` given in array passed to Sql->prepare(`' . $stmt .
                                            '`) pattern, only scalar (int, float, string, bool) and NULL values are allowed in `?` statements!');
                                }
                                                $comma = ', ';
                            }
                                return $result;
                            //}
                        }

                                /**
                                 *
                                 *  Creating a `sub-pattern` of code within the [...] array syntax
                                 *
                                 */
                        return (string) new self($matches[1], $array);

                    default:
                        $count++;

                        if ($match[0] === '%') {
                            $command = $matches[2];
                            if ($command === '') {    //    for '%%' && '\%', $match === $matches[0] === "%%" && $command === $matches[2] === ""
                                return '%';
                            }

                            $value = current($params);
                            $index = key($params);          // too complicated to backtrack (with prev(), key(), end() bla bla) in this handler like the others, just store the damn index!
                            if ($value === false && $index === null) {
                                throw new \BadMethodCallException('Invalid number of parameters (' . count($params) .
                                    ') supplied to Sql->prepare(`' . $stmt .
                                    '`) pattern! Please check the number of `?`, `@` and `%` values in the pattern, expecting at least one more!');
                            }
                            $next = next($params);
                            //  detect `call(able)` method in $next and skip!
                            //  because some commands might accept a `callable` for error handling
                            if (is_callable($next)) {
                                next($params);  // skip the callable by moving to next parameter!
                            }
                        } else {
                            if (strpos($matches[0], '..', 1)) {
                                $range = explode('..', $matches[0]);
                                if (count($range) === 2) {
                                    $min = $range[0];
                                    $max = $range[1];
                                    if ((is_numeric($min) || $min === '?') && (is_numeric($max) || $max === '?')) {
                                        $count--;   //  we need to `re-calculate` the paramater count. Because this command can take 0..2 parameters
                                        if ($min === '?') {
                                            $min = current($params);
                                            $index = key($params);
                                            if ($min === false && $index === null) {
                                                throw new \BadMethodCallException('Invalid number of parameters (' . count($params) .
                                                    ') supplied to Sql->prepare(`' . $stmt .
                                                    '`) pattern! Please check the number of `?`, `@` and `%` values in the pattern, expecting at least one more!');
                                            }
                                            next($params);
                                            $count++;
                                        }
                                        if ($max === '?') {
                                            $max = current($params);
                                            $index = key($params);
                                            if ($max === false && $index === null) {
                                                throw new \BadMethodCallException('Invalid number of parameters (' . count($params) .
                                                    ') supplied to Sql->prepare(`' . $stmt .
                                                    '`) pattern! Please check the number of `?`, `@` and `%` values in the pattern, expecting at least one more!');
                                            }
                                            next($params);
                                            $count++;
                                        }

                                        if (! is_numeric($min) || ! is_numeric($max)) {
                                            throw new \BadMethodCallException('Invalid parameters for range generator `' . $matches[0] . '` supplied to Sql->prepare(`' . $stmt .
                                                    '`) pattern! Please check that both values are numeric. Ranges can only include integers or `?`, eg. ?..?, 1..?, 1..10; ' .
                                                    (is_numeric($min) ? null : $min . ' value supplied as min;') . (is_numeric($max) ? null : $max . ' value supplied as max.'));
                                        }

                                        return implode(', ', range($min, $max));
                                    } /*else {
                                        throw new \BadMethodCallException('Invalid parameters for range generator `' . $matches[0] . '` supplied to Sql->prepare(`' . $stmt .
                                                '`) pattern! Ranges can only include integers or `?`, eg. ?..?, 1..?, 1..10');

                                    }*/
                                }
                            }

                            /**
                             *  This section of code is used to support the `PDO::prepare()` syntax
                             *  eg. `->prepare('[:id]', ['id' => 555]);
                             *  returns: "555"
                             */
                            /**
                             *  Doing an `isset()` test first because it's faster, but doesn't pass when the array value is null
                             *  That's what the `array_key_exists()` test if for!
                             */
                            if (isset($params[$key = $matches[2]]) || array_key_exists($key, $params) || isset($params[$key = $matches[0]]) || array_key_exists($key, $params)) {
                                $value = $params[$key];

                                if (is_numeric($value) && (is_int($value) || is_float($value) || (string) $value === (string) (float) $value)) {
                                    $command = 'd';
                                } elseif (is_string($value)) {
                                    $command = 's';
                                } elseif (is_null($value)) {
                                    return 'NULL';
                                } elseif (is_bool($value)) {
                                    return $value ? '1' : '0';      //  bool values return '' when false
                                } elseif (is_array($value)) {      //   same code used in [?]
                                    $comma = null;
                                    $result = '';
                                    foreach ($value as $v) {
                                        if (is_numeric($v) && (is_int($v) || is_float($v) || (string) $v === (string) (float) $v)) {
                                            $result .= $comma . $v;
                                        } elseif (is_string($v)) {
                                            $result .= $comma . self::quote($v);
                                        } elseif (is_null($v)) {
                                            $result .= $comma . 'NULL';
                                        } elseif (is_bool($v)) {
                                            $result .= $comma . $v ? '1' : '0';
                                        } else {
                                            throw new \InvalidArgumentException('Invalid data type `' . (is_object($value) ? get_class($value) : gettype($value)) .
                                                    '` given in array passed to Sql->prepare(`' . $stmt .
                                                    '`) pattern, only scalar (int, float, string, bool) and NULL values are allowed in `?` statements!');
                                        }
                                                        $comma = ', ';
                                    }
                                    return $result;
                                } else {
                                    throw new \InvalidArgumentException('Invalid data type `' . (is_object($value) ? get_class($value) : gettype($value)) .
                                                    '` passed to Sql->prepare(`' . $stmt .
                                                    '`) pattern, only scalar (int, float, string, bool), NULL and single dimension arrays are allowed!');
                                }
                            } else {
                                if (isset($params[$key = '@' . $matches[2]]) || array_key_exists($key, $params)) {    //  @id
                                    $value = $params[$key];

                                    if (is_string($value)) {
                                        return $value;  //  first test for a string because it's the most common case for @
                                    }
                                    if (is_numeric($value) && (is_int($value) || is_float($value) || (string) $value === (string) (float) $value)) {
                                        return (string) $value;
                                    }
                                    if (is_null($value)) {
                                        return 'NULL';
                                    }
                                    if (is_bool($value)) {
                                        return $value ? '1' : '0';  //  bool values return '' when false
                                    }
                                    if (is_array($value)) {
                                        return implode(', ', $value);   //  WARNING: This isn't testing NULL and bool!
                                    }

                                    throw new \InvalidArgumentException('Invalid data type `' . (is_object($value) ? get_class($value) : gettype($value)) .
                                                    '` passed to Sql->prepare(`' . $stmt .
                                                    '`) pattern, only scalar (int, float, string, bool), NULL and single dimension arrays are allowed!');
                                } else {
                                    throw new \InvalidArgumentException('Invalid array index `' . $matches[0] . '` for Sql->prepare(`' . $stmt . '`) pattern!');
                                }
                            }
                        }


                        if (! empty($matches[4])) {
                            $matches[4] = rtrim(ltrim($matches[4], '{'), '}');
                        }
                        $modifiers = $matches[3] . (empty($matches[4]) ? null : ':' . $matches[4]);

                        if (is_null($value)) {
                            //  working, but (future) support for regular expressions might create false positives
                            if (preg_match('~[\{:]n(ull(able)?)?([:\{\}]|$)~', $modifiers)) {
                                return 'NULL';
                            }
                            throw new \InvalidArgumentException('NULL value detected for a non-nullable field at index ' . $index . ' for command: `' . $matches[0] . '`');
                        }

                        if (isset(self::$modifiers[$command])) {
                            if (call_user_func(self::$types[$command], $value, $modifiers, 'init')) {
                                return $value;
                            }
                        }

                        if (isset(self::$types[$command])) {
                        //  Cannot use call_user_func() with a value reference ... 2 different errors ... one when I try `&$value`
                        //  Parse error: syntax error, unexpected '&' in Sql.php on line ...
                        //  Warning: Parameter 1 to {closure}() expected to be a reference, value given in Sql.php on line ...
                        //  $result = call_user_func(self::$types[$command], $value, $modifiers);
                            $result = self::$types[$command]($value, $modifiers);
                            if (is_string($result)) {
                                return $result;
                            }
                        }

                        switch ($command) {
                            case 'string':
                            case 'varchar':             //  varchar:trim:crop:8:100 etc. ... to enable `cropping` to the given sizes, without crop, we throw an exception when the size isn't right! and trim to trim it!
                            case 'char':                //  :normalize:pack:tidy:minify:compact ... pack the spaces !?!? and trim ...  `minify` could be used for JavaScript/CSS etc.
                            case 'text':                //  I think we should use `text` only to check for all the modifiers ... so we don't do so many tests for common %s values ... this is `text` transformations ...
                            case 's':
                                //  WARNING: We need to handle the special case of `prepare('%s:json_encode', ['v2', 'v2'])` ... where the first param is an array ...

                                //  empty string = NULL
                                if (strpos($modifiers, ':json') !== false) {
                                    if (isset($params_conversion) && $params_conversion) {  //  the first $param[0] WAS an array (as tested at the top) ... and there was only one value ...
                                        $value  =   $params;                                //  $params IS an array and IS our actual value, not the first value OF params!
                                    }
                                    if (is_array($value)) {
                                        //  loop through the values and handle :trim :pack etc. on them
                                        if (strpos($modifiers, ':pack') !== false) {
                                            foreach ($value as $json_key => $json_value) {
                                                if (is_string()) {
                                                    $json_value = trim(mb_ereg_replace('\s+', ' ', $value));
                                                } elseif (is_numeric()) {
                                                    $json_value = trim(mb_ereg_replace('\s+', ' ', $value));
                                                }
                                            }
                                        } elseif (strpos($modifiers, ':trim') !== false) {
                                            foreach ($value as $json_key => $json_value) {
                                                $json_value = trim(mb_ereg_replace('\s+', ' ', $value));
                                            }
                                        }
                                    }

                                    //  ordered by most common
                                    if (strpos($modifiers, ':jsonencode') !== false) {
                                        $value = json_encode($value);
                                    } elseif (strpos($modifiers, ':json_encode') !== false) {    //   `_` is giving problems in the regular expression! Dunno why!
                                        $value = json_encode($value);
                                    } elseif (strpos($modifiers, ':jsonify') !== false) {
                                        $value = json_encode($value);
                                    } elseif (strpos($modifiers, ':to_json') !== false) {
                                        $value = json_encode($value);
                                    } elseif (strpos($modifiers, ':json_decode') !== false) {    //   WARNING: only string values in :json_decode are valid! So it has limited application!
                                        $value = json_decode($value);
                                    } elseif (strpos($modifiers, ':from_json') !== false) {
                                        $value = json_decode($value);
                                    } elseif (strpos($modifiers, ':fromjson') !== false) {
                                        $value = json_decode($value);
                                    }
                                }

                                if (! is_string($value)) {
                                    throw new \InvalidArgumentException('Invalid data type `' . (is_object($value) ? get_class($value) : gettype($value)) .
                                                    '` given at index ' . $index . ' passed in Sql->prepare(`' . $stmt .
                                                    '`) pattern, only string values are allowed for %s statements!');
                                }

                            //  $modifiers = array_flip(explode(':', $modifiers));  //  strpos() is probably still faster!

                                if (strpos($modifiers, ':pack') !== false) {
                                    $value = trim(mb_ereg_replace('\s+', ' ', $value));
                                } elseif (strpos($modifiers, ':trim') !== false) {
                                    $value = trim($value);
                                }

                                //  empty string = NULL
                                if (strpos($modifiers, ':enull') !== false && empty($value)) {
                                    return 'NULL';
                                }

                                if ($command === 'text') {  //  `text` only modifiers ... not necessarily the `text` data types, just extra `text` modifiers
                                    if (strpos($modifiers, ':tolower') !== false || strpos($modifiers, ':lower') !== false || strpos($modifiers, ':lcase') !== false) {
                                        $value = mb_strtolower($value);
                                    }

                                    if (strpos($modifiers, ':toupper') !== false || strpos($modifiers, ':upper') !== false || strpos($modifiers, ':ucase') !== false) {
                                        $value = mb_strtoupper($value);
                                    }

                                    if (strpos($modifiers, ':ucfirst') !== false) {
                                        $value = mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1);
                                    }

                                    if (strpos($modifiers, ':ucwords') !== false) {
                                        $value = mb_convert_case($value, MB_CASE_TITLE);
                                    }

                                    if (strpos($modifiers, ':md5') !== false) { //  don't :pack if you are hashing passwords!
                                        $value = md5($value);
                                    }

                                    if (strpos($modifiers, ':sha') !== false) {
                                        if (strpos($modifiers, ':sha1') !== false) {
                                            $value = hash('sha1', $value);
                                        } elseif (strpos($modifiers, ':sha256') !== false) {
                                            $value = hash('sha256', $value);
                                        } elseif (strpos($modifiers, ':sha384') !== false) {
                                            $value = hash('sha384', $value);
                                        } elseif (strpos($modifiers, ':sha512') !== false) {
                                            $value = hash('sha512', $value);
                                        }
                                    }
                                }

                                preg_match('~(?:(?::\d*)?:\d+)~', $modifiers, $range);
                                if (! empty($range)) {
                                    $range = ltrim($range[0], ':');
                                    if (is_numeric($range)) {
                                        $min = 0;
                                        $max = $range;
                                    } else {
                                        $range = explode(':', $range);
                                        if (count($range) !== 2 || ! empty($range[0]) && ! is_numeric($range[0]) || ! empty($range[1]) && ! is_numeric($range[1])) {
                                            throw new \InvalidArgumentException("Invalid syntax detected for `%{$command}` statement in `{$matches[0]}`
																	given at index {$index} for Sql->prepare(`{$stmt}`) pattern;
																	`%{$command}` requires valid numeric values. eg. %{$command}:10 or %{$command}:8:50");
                                        }
                                        $min = $range[0];
                                        $max = $range[1];
                                    }

                                    $strlen = mb_strlen($value);
                                    if ($min && $strlen < $min) {
                                            throw new \InvalidArgumentException("Invalid string length detected for `%{$command}` statement in
																	`{$matches[0]}` given at index {$index} for Sql->prepare(`{$stmt}`) pattern;
																	`{$matches[0]}` requires a string to be a minimum {$min} characters in length; input string has only {$strlen} of {$min} characters");
                                    }
                                    if ($max && $strlen > $max) {
                                        if (strpos($modifiers, ':crop') !== false) {
                                            $value = mb_substr($value, 0, $max);
                                        } else {
                                            throw new \InvalidArgumentException("Invalid string length detected for `%{$command}` statement in `{$matches[0]}`
																	given at index {$index} for Sql->prepare(`{$stmt}`) pattern; `{$matches[0]}` requires a string to be maximum `{$max}`
																	size, and cropping is not enabled! To enable auto-cropping specify: `{$command}:{$min}:{$max}:crop`");
                                        }
                                    }
                                }

                                //  :raw = :noquot + :noescape
                                if (strpos($modifiers, ':raw') !== false) {
                                    return $value;
                                }

                                $noquot     = strpos($modifiers, ':noquot') !== false;
                                $noescape   = strpos($modifiers, ':noescape')   !== false;
                            //  $utf8mb4    = strpos($modifiers, ':utf8mb4')    !== false || strpos($modifiers, ':noclean') !== false;  // to NOT strip 4-byte UTF-8 characters (MySQL has issues with them and utf8 columns, must use utf8mb4 table/column and connection, or MySQL will throw errors)

                                return ($noquot ? null : self::$quot) . ($noescape ? $value : self::escape($value)) . ($noquot ? null : self::$quot);
                            //  return ($noquot ? null : self::$quot) . ($noescape ? $value : self::escape($utf8mb4 ? $value : self::utf8($value))) . ($noquot ? null : self::$quot);


                            case 'd':
                            case 'f';
                            case 'e';
                            case 'float';
                            case 'id':
                            case 'int':
                            case 'byte':
                            case 'bit':
                            case 'integer':
                            case 'unsigned';

                                if (is_numeric($value)) {
                                    if (strpos($modifiers, ':clamp') !== false) {
                                        preg_match('~:clamp:(?:([-+]?[0-9]*\.?[0-9]*):)?([-+]?[0-9]*\.?[0-9]*)~', $modifiers, $range);
                                        if (empty($range)) {
                                            throw new \InvalidArgumentException("Invalid %{$command}:clamp syntax `{$matches[0]}`
																detected for call to Sql->prepare(`{$stmt}`) at index {$index};
																%{$command}:clamp requires a numeric range: eg. %{$command}:clamp:10 or %{$command}:clamp:1:10");
                                        }
                                        $value = min(max($value, is_numeric($range[1]) ? $range[1] : 0), is_numeric($range[2]) ? $range[2] : PHP_INT_MAX);
                                    }
                                    return $value;
                                }

                                throw new \InvalidArgumentException('Invalid data type `' . (is_object($value) ? get_class($value) : gettype($value)) .
                                                '` given at index ' . $index . ' passed in Sql->prepare(`' . $stmt .
                                                '`) pattern, only numeric data types (integer and float) are allowed for %d and %f statements!');

                            case 'clamp';

                                if (! is_numeric($value)) {
                                    throw new \InvalidArgumentException('Invalid data type `' . (is_object($value) ? get_class($value) : gettype($value)) .
                                                    '` given at index ' . $index . ' passed in Sql->prepare(`' . $stmt .
                                                    '`) pattern, only numeric data types (integer and float) are allowed for %clamp statements!');
                                }

                                preg_match('~(?:(?::[-+]?[0-9]*\.?[0-9]*)?:[-+]?[0-9]*\.?[0-9]+)~', $modifiers, $range);

                                if (empty($range)) {
                                    throw new \InvalidArgumentException('Invalid %clamp syntax `' . $matches[0] .
                                                '` detected for call to Sql->prepare(`' . $stmt .
                                                '`) at index ' . $index . '; %clamp requires a numeric range: eg. %clamp:1:10');
                                }
                                $range = ltrim($range[0], ':');
                                if (is_numeric($range)) {
                                    $value = min(max($value, 0), $range);
                                } else {
                                    $range = explode(':', $range);
                                    if (count($range) !== 2 || ! empty($range[0]) && ! is_numeric($range[0]) || ! empty($range[1]) && ! is_numeric($range[1])) {
                                        throw new \InvalidArgumentException('Invalid syntax detected for %clamp statement in `' . $matches[0] .
                                                        '` given at index ' . $index . ' for Sql->prepare(`' . $stmt .
                                                        '`) pattern; %clamp requires valid numeric values. eg. %clamp:0.0:1.0 or %clamp:1:100 or %clamp::100 or %clamp:-10:10');
                                    }
                                    $value = min(max($value, $range[0]), $range[1]);
                                }

                                return $value;

                            case 'bool':
                            case 'boolean':
                            case 'date':
                            case 'datetime';
                            case 'timestamp';
                        }
                        return $value;
                }
            },
            $stmt
        );

        if ($count !== count($params) && ! isset($params_conversion)) {
            throw new \BadMethodCallException('Invalid number of parameters (' . count($params) .
                ') supplied to Sql->prepare(`' . $stmt .
                '`) statement pattern! Explecting ' . $count . ' for this pattern but received ' . count($params));
        }
        return $this;
    }


    /**
     *  Set the database connection
     *
     *  The escape handlers will be automatically derived from your connection object or resource
     *
     *  Examples:
     *
     *      \Twister\Sql::setConnection($conn);
     *      \Twister\Sql::setConnection($dbconn);
     *      \Twister\Sql::setConnection($pdo);
     *      \Twister\Sql::setConnection($mysqli);
     *      \Twister\Sql::setConnection($mysql);
     *      \Twister\Sql::setConnection($link);
     *      \Twister\Sql::setConnection($sqlite);
     *      \Twister\Sql::setConnection($odbc);
     *
     *  Oracle:
     *  https://docs.oracle.com/cd/B28359_01/text.111/b28304/cqspcl.htm#CCREF2091
     *
     *  @param  string $conn Database connection object, resource or null
     *
     *  @return void
     */
    public static function setConnection($conn = null)
    {
        if (is_object($conn)) {
            if ($conn instanceof \PDO) {
                self::$exec =
                    function ($sql) use ($conn) {
                        return $conn->exec($sql);
                    };
                self::$execute =
                    function ($sql) use ($conn) {
                        return $conn->query($sql);
                    };
                self::$query =
                    function ($sql) use ($conn) {
                        return $conn->query($sql);
                    };
                self::$lookup =
                    function ($sql) use ($conn) {
                        $recset = $conn->query($sql);
                        if (! $recset) {
                            throw new \Exception('PDO::query() error: ' . $conn->errorInfo()[2]);
                        }
                        $result = $recset->fetchAll(\PDO::FETCH_ASSOC);
                        $recset->closeCursor();
                        $result = array_shift($result);
                        return count($result) === 1 ? array_shift($result) : $result;
                    };
                self::$fetchAll =
                    function ($sql) use ($conn) {
                        $recset = $conn->query($sql);
                        if (! $recset) {
                            throw new \Exception('PDO::query() error: ' . $conn->errorInfo()[2]);
                        }
                        $result = $recset->fetchAll(\PDO::FETCH_ASSOC);
                        $recset->closeCursor();
                        return $result;
                    };
                self::$fetchAllIndexed =
                    function ($sql, $index) use ($conn) {
                        $recset = $conn->query($sql);
                        if (! $recset) {
                            throw new \Exception('PDO::query() error: ' . $conn->errorInfo()[2]);
                        }
                        $tmp = $recset->fetchAll(\PDO::FETCH_ASSOC);
                        $recset->closeCursor();
                        $result = [];
                        foreach ($tmp as $row) {
                            $result[$row[$index]] = $row;
                        }
                        return $result;
                    };
                self::$fetchNum =
                    function ($sql) use ($conn) {
                        $recset = $conn->query($sql);
                        if (! $recset) {
                            throw new \Exception('PDO::query() error: ' . $conn->errorInfo()[2]);
                        }
                        $result = $recset->fetchAll(\PDO::FETCH_NUM);
                        $recset->closeCursor();
                        return $result;
                    };

                self::$quot = substr($conn->quote(''), 0, 1);
                self::$escape_handler =
                    function ($string) use ($conn) {
                        return substr(substr($conn->quote($string), 1), 0, -1);
                    };
                self::$quote_handler =
                    function ($string) use ($conn) {
                        return $conn->quote($string);
                    };
                return;
            } elseif ($conn instanceof \MySQLi) {
                self::$exec =
                    function ($sql) use ($conn) {
                        $conn->real_query($sql);
                        return $conn->affected_rows;
                    };
                self::$execute =
                    function ($sql) use ($conn) {
                        return $conn->query($sql);
                    };
                self::$query =
                    function ($sql) use ($conn) {
                        return $conn->query($sql);
                    };
                self::$lookup =
                    function ($sql) use ($conn) {
                        $recset = $conn->query($sql);
                        if (! $recset) {
                            throw new \Exception('MySQLi::query() error: ' . $conn->error);
                        }
                        if ($recset->field_count == 1) {
                            $result = $recset->fetch_row();
                            $recset->free_result();
                            return $result[0];
                        }
                        $result = $recset->fetch_assoc();
                        $recset->free();
                        return $result;
                    };
                self::$fetchAll =
                    function ($sql) use ($conn) {
                        $recset = $conn->query($sql);
                        if (! $recset) {
                            throw new \Exception('MySQLi::query() error: ' . $conn->error);
                        }
                        $result = $recset->fetch_all(MYSQLI_ASSOC);
                        $recset->free();
                        return $result;
                    };
                self::$fetchAllIndexed =
                    function ($sql, $index) use ($conn) {
                        $recset = $conn->query($sql);
                        if (! $recset) {
                            throw new \Exception('MySQLi::query() error: ' . $conn->error);
                        }
                        $tmp = $recset->fetch_all(MYSQLI_ASSOC);
                        $recset->free();
                        $result = [];
                        foreach ($tmp as $row) {
                            $result[$row[$index]] = $row;
                        }
                        return $result;
                    };
                self::$fetchNum =
                    function ($sql) use ($conn) {
                        $recset = $conn->query($sql);
                        if (! $recset) {
                            throw new \Exception('MySQLi::query() error: ' . $conn->error);
                        }
                        $result = $recset->fetch_all(MYSQLI_NUM);
                        $recset->free();
                        return $result;
                    };

                self::$escape_handler =
                    function ($string) use ($conn) {
                        return $conn->real_escape_string($string);
                    };
                self::$quote_handler =
                    function ($string) use ($conn) {
                        return self::$quot . $conn->real_escape_string($string) . self::$quot;
                    };
                return;
            } elseif ($conn instanceof \SQLite3) {
                self::$exec =
                    function ($sql) use ($conn) {
                        $conn->exec($sql);
                        return $conn->affected_rows;
                    };
                self::$execute =
                    function ($sql) use ($conn) {
                        return $conn->query($sql);
                    };
                self::$query =
                    function ($sql) use ($conn) {
                        return $conn->query($sql);
                    };
                self::$lookup =
                    function ($sql) use ($conn) {
                        $result = $conn->querySingle($sql, true);
                        return count($result) === 1 ? array_shift($result) : $result;
                    };
                self::$fetchAll =
                    function ($sql) use ($conn) {
                        $recset = $conn->query($sql);
                        $result = [];
                        while ($row = $recset->fetchArray($mode, SQLITE3_ASSOC)) {
                            $result[] = $row;
                        }
                        $recset->finalize();
                        return $result;
                    };
                self::$fetchAllIndexed =
                    function ($sql, $index) use ($conn) {
                        $recset = $conn->query($sql);
                        $result = [];
                        while ($row = $recset->fetchArray($mode, SQLITE3_ASSOC)) {
                            $result[$row[$index]] = $row;
                        }
                        $recset->finalize();
                        return $result;
                    };
                self::$fetchNum =
                    function ($sql) use ($conn) {
                        $recset = $conn->query($sql);
                        $result = [];
                        while ($row = $recset->fetchArray($mode, SQLITE3_NUM)) {
                            $result[] = $row;
                        }
                        $recset->finalize();
                        return $result;
                    };

                self::$escape_handler =
                    function ($string) use ($conn) {
                        return $conn->real_escape_string($string);
                    };
                self::$quote_handler =
                    function ($string) use ($conn) {
                        return self::$quot . $conn->real_escape_string($string) . self::$quot;
                    };
                return;
            }
        }
        /**
         *  {@link http://php.net/resource}
         */
        if (is_resource($conn)) {
            switch (get_resource_type()) {
                case 'pgsql link':
                case 'pgsql link persistent':
                    trigger_error('experimental driver', E_USER_NOTICE);
                    self::$exec =
                        function ($sql) use ($conn) {
                            $recset = pg_query($conn, $sql);
                            if (! $recset) {
                                throw new \Exception('pg_query() error: ' . pg_last_error($conn));
                            }
                            pg_free_result($recset);
                            return pg_affected_rows($conn);
                        };
                    self::$execute =
                        function ($sql) use ($conn) {
                            return pg_query($conn, $sql);
                        };
                    self::$query =
                        function ($sql) use ($conn) {
                            return pg_query($conn, $sql);
                        };
                    self::$lookup =
                        function ($sql) use ($conn) {
                            $recset = pg_query($conn, $sql);
                            if (! $recset) {
                                throw new \Exception('pg_query() error: ' . pg_last_error($conn));
                            }
                            $result = pg_fetch_all($recset);
                            pg_free_result($recset);
                            $result = array_shift($result);
                            return count($result) === 1 ? array_shift($result) : $result;
                        };
                    self::$fetchAll =
                        function ($sql) use ($conn) {
                            $recset = pg_query($conn, $sql);
                            if (! $recset) {
                                throw new \Exception('pg_query() error: ' . pg_last_error($conn));
                            }
                            $result = pg_fetch_all($recset);
                            pg_free_result($recset);
                            return $result;
                        };
                    self::$fetchAllIndexed =
                        function ($sql, $index) use ($conn) {
                            $recset = pg_query($conn, $sql);
                            if (! $recset) {
                                throw new \Exception('pg_query() error: ' . pg_last_error($conn));
                            }
                            $tmp = pg_fetch_all($recset);
                            pg_free_result($recset);
                            $result = [];
                            foreach ($tmp as $row) {
                                $result[$row[$index]] = $row;
                            }
                            return $result;
                        };
                    self::$fetchNum =
                        function ($sql) use ($conn) {
                            $recset = pg_query($conn, $sql);
                            if (! $recset) {
                                throw new \Exception('pg_query() error: ' . pg_last_error($conn));
                            }
                            $tmp = pg_fetch_all($recset);
                            pg_free_result($recset);
                            $result = [];
                            foreach ($tmp as $row) {
                                $result[] = array_values($row);
                            }
                            return $result;
                        };

                    self::$quot = '\'';
                    self::$escape_handler =
                        function ($string) use ($conn) {
                            return pg_escape_string($conn, $string);
                        };
                    self::$quote_handler =
                        function ($string) use ($conn) {
                            return pg_escape_literal($conn, $string);
                        };
                    return;
                case 'mysql link':
                case 'mysql link persistent':
                    trigger_error('experimental driver', E_USER_NOTICE);
                    self::$exec =
                        function ($sql) use ($conn) {
                            mysql_query($sql, $conn);
                            return mysql_affected_rows($conn);
                        };
                    self::$execute =
                        function ($sql) use ($conn) {
                            return mysql_query($sql, $conn);
                        };
                    self::$query =
                        function ($sql) use ($conn) {
                            return mysql_query($sql, $conn);
                        };
                    self::$lookup =
                        function ($sql) use ($conn) {
                            $recset = mysql_query($sql, $conn);
                            if (! $recset) {
                                throw new \Exception('mysql_query() error: ' . mysql_error($conn));
                            }
                            $result = mysql_fetch_assoc($recset);
                            mysql_free_result($recset);
                            return count($result) === 1 ? array_shift($result) : $result;
                        };
                    self::$fetchAll =
                        function ($sql) use ($conn) {
                            $recset = mysql_query($sql, $conn);
                            if (! $recset) {
                                throw new \Exception('mysql_query() error: ' . mysql_error($conn));
                            }
                            $result = [];
                            while ($row = mysql_fetch_assoc($recset)) {
                                $result[] = $row;
                            }
                            mysql_free_result($recset);
                            return $result;
                        };
                    self::$fetchAllIndexed =
                        function ($sql, $index) use ($conn) {
                            $recset = mysql_query($sql, $conn);
                            if (! $recset) {
                                throw new \Exception('mysql_query() error: ' . mysql_error($conn));
                            }
                            $result = [];
                            while ($row = mysql_fetch_assoc($recset)) {
                                $result[$row[$index]] = $row;
                            }
                            mysql_free_result($recset);
                            return $result;
                        };
                    self::$fetchNum =
                        function ($sql) use ($conn) {
                            $recset = mysql_query($sql, $conn);
                            if (! $recset) {
                                throw new \Exception('mysql_query() error: ' . mysql_error($conn));
                            }
                            $result = [];
                            while ($row = mysql_fetch_row($recset)) {
                                $result[] = $row;
                            }
                            mysql_free_result($recset);
                            return $result;
                        };

                    self::$escape_handler =
                        function ($string) use ($conn) {
                            return mysql_real_escape_string($string, $conn);
                        };
                    self::$quote_handler =
                        function ($string) use ($conn) {
                            return self::$quot . mysql_real_escape_string($string, $conn) . self::$quot;
                        };
                    return;
            }
        }
        if ($conn === null) {
            self::$escape_handler   =   '\\Twister\\Sql::default_escape_string';
            self::$quote_handler    =   '\\Twister\\Sql::default_quote_string';
            self::$exec             =   '\\Twister\\Sql::noConnError';
            self::$execute          =   '\\Twister\\Sql::noConnError';
            self::$query            =   '\\Twister\\Sql::noConnError';
            self::$lookup           =   '\\Twister\\Sql::noConnError';
            self::$fetchAll         =   '\\Twister\\Sql::noConnError';
            self::$fetchAllIndexed  =   '\\Twister\\Sql::noConnError';
            self::$fetchNum         =   '\\Twister\\Sql::noConnError';
        }
        throw new \Exception('Invalid database type, object, resource or string. No compatible driver detected!');
    }


    /**
     *  Escape a string for use with a LIKE clause
     *
     *  When using user input in a LIKE clause, both MySQL and PostgreSQL require
     *  the `%` and `_` characters in $string to be escaped with `\`,
     *  because they have special meaning in a LIKE clause
     *
     *  In LIKE statements; `_` matches 'any single character', similar to `.` in regular expressions,
     *  and the `_` character can be placed anywhere; eg. `LIKE 'Jas_n'`.
     *  So `_` should be properly escaped when used with strings provided from user input in LIKE statements!
     *
     *  $pattern should contain a `?` in the place of $string
     *  eg.
     *       ->like('?%', $string)
     *       ->like('%?', $string)
     *       ->like('%?%', $string)
     *  or
     *       ->LIKE('?%', $string)
     *       ->LIKE('%?', $string)
     *       ->LIKE('%?%', $string)
     *
     *  ODBC:
     *  @link https://docs.microsoft.com/en-us/sql/odbc/reference/develop-app/like-predicate-escape-character
     *
     *  @param  string $pattern The pattern to use, use `?` in the place of your string
     *  @param  string $string  The string that needs special 'LIKE' escaping
     *
     *  @return $this
     */
    public function like($pattern, $string)
    {
        $pattern = trim($pattern, self::$quot); // remove self::$quot (" or ') from $pattern
        $this->sql .= self::$translations['LIKE'] . self::$quot . str_replace('?', mb_ereg_replace('[%_]', '\\\0', self::escape($string)), $pattern) . self::$quot;
        return $this;
    }


    /**
     *  Escape a string for use with a NOT LIKE clause
     *
     *  When using user input in a NOT LIKE clause, both MySQL and PostgreSQL require
     *  the `%` and `_` characters in $string to be escaped with `\`,
     *  because they have special meaning in a NOT LIKE clause
     *
     *  In NOT LIKE statements; `_` matches 'any single character', similar to `.` in regular expressions,
     *  and the `_` character can be placed anywhere; eg. `NOT LIKE 'Jas_n'`.
     *  So `_` should be properly escaped when used with strings provided from user input in NOT LIKE statements!
     *
     *  $pattern should contain a `?` in the place of $string
     *  eg.
     *       ->notLike('?%', $string)
     *       ->notLike('%?', $string)
     *       ->notLike('%?%', $string)
     *
     *  @param  string $pattern The pattern to use, use `?` in the place of your string
     *  @param  string $string  The string that needs special 'NOT LIKE' escaping
     *
     *  @return $this
     */
    public function notLike($pattern, $string)
    {
        $pattern = trim($pattern, self::$quot); // remove self::$quot (" or ') from $pattern
        $this->sql .= self::$translations['NOT_LIKE'] . self::$quot . str_replace('?', self::escape(mb_ereg_replace('[%_]', '\\\0', $string)), $pattern) . self::$quot;
        return $this;
    }


    /**
     *  Escape a string for use with a NOT LIKE clause
     *
     *  When using user input in a NOT LIKE clause, both MySQL and PostgreSQL require
     *  the `%` and `_` characters in $string to be escaped with `\`,
     *  because they have special meaning in a NOT LIKE clause
     *
     *  In NOT LIKE statements; `_` matches 'any single character', similar to `.` in regular expressions,
     *  and the `_` character can be placed anywhere; eg. `NOT LIKE 'Jas_n'`.
     *  So `_` should be properly escaped when used with strings provided from user input in NOT LIKE statements!
     *
     *  $pattern should contain a `?` in the place of $string
     *  eg.
     *       ->not_like('?%', $string)
     *       ->not_like('%?', $string)
     *       ->not_like('%?%', $string)
     *  or
     *       ->NOT_LIKE('%?%', $string)
     *       ->NOT_LIKE('%?%', $string)
     *       ->NOT_LIKE('%?%', $string)
     *
     *  @param  string $pattern The pattern to use, use `?` in the place of your string
     *  @param  string $string  The string that needs special 'NOT LIKE' escaping
     *
     *  @return $this
     */
    public function not_like($pattern, $string)
    {
        $pattern = trim($pattern, self::$quot); // remove self::$quot (" or ') from $pattern
        $this->sql .= self::$translations['NOT_LIKE'] . self::$quot . str_replace('?', self::escape(mb_ereg_replace('[%_]', '\\\0', $string)), $pattern) . self::$quot;
        return $this;
    }


    /**
     *  Escape a string for use in a query
     *
     *  This function will 'escape' an ASCII or Multibyte string
     *
     *  By default, the function uses MySQL rules.
     *  To change the internal escaper, set the connection with
     *
     *      `Twister\Sql::setConnection($dbconn);`
     *
     *  The `setConnection()` function automatically detects the connection/object type
     *  and sets the internal escape handler.
     *
     *  Or you can manually set the string escaper by calling:
     *
     *      `Twister\Sql::setEscaper('mysql' | 'postgresql' | 'pdo' | 'sqlite');`
     *
     *  By default, this function escapes exactly the same characters as `mysqli::real_escape_string`
     *  `Characters encoded are NUL (ASCII 0), \n, \r, \, ', ", and Control-Z.` ctl-Z = dec:26 hex:1A
     *
     *  Note: This function is Multibyte-aware; typically UTF-8 but depends on your `mb_internal_encoding()`
     *
     *  Notes on `mysqli::real_escape_string`
     *  @link   http://php.net/manual/en/mysqli.real-escape-string.php#46339
     *      `Note that this function will NOT escape _ (underscore) and % (percent) signs, which have special meanings in LIKE clauses.`
     *
     *  MySQL:       {@link https://dev.mysql.com/doc/refman/5.7/en/string-literals.html}
     *  PostgreSQL:  {@link https://www.postgresql.org/docs/9.2/static/sql-syntax-lexical.html}
     *  Oracle:      {@link https://docs.oracle.com/cd/B28359_01/text.111/b28304/cqspcl.htm#CCREF2091}
     *
     *  PostgreSQL:
     *      PostgreSQL supports almost the same escape sequences.
     *      However, it doesn't require \0 (NUL) or ctrl-Z (\Z) to be escaped.
     *      From version 9.1, PostgreSQL requires changes to standard_conforming_strings to handle these strings, or the use of `E'...'`
     *      {@link https://www.postgresql.org/docs/9.2/static/runtime-config-compatible.html#GUC-STANDARD-CONFORMING-STRINGS}
     *      'Applications that wish to use backslash as escape should be modified to use escape string syntax (E'...'),
     *          because the default behavior of ordinary strings is now to treat backslash as an ordinary character,
     *          per SQL standard. This variable can be enabled to help locate code that needs to be changed.'
     *
     *  @param  string $string The string you want to escape and quote
     *
     *  @return string
     */
    public static function escape($string)
    {
        return call_user_func(self::$escape_handler, $string);
    }


    /**
     *  Quote a string for use in a query
     *
     *  This function will 'quote' AND 'escape' an ASCII or Multibyte string
     *  Internally the function executes something like mb_ereg_replace('[\'\"\n\r\0]', '\\\0', $string)
     *
     *  This function escapes exactly the same characters as `mysqli::real_escape_string`
     *  `Characters encoded are NUL (ASCII 0), \n, \r, \, ', ", and Control-Z.` ctl-Z = dec:26 hex:1A
     *
     *  Note: This function is Multibyte-aware; typically UTF-8 but depends on your `mb_internal_encoding()`
     *
     *  Notes on `mysqli::real_escape_string`
     *  @link   http://php.net/manual/en/mysqli.real-escape-string.php#46339
     *      `Note that this function will NOT escape _ (underscore) and % (percent) signs, which have special meanings in LIKE clauses.`
     *
     *  Note: This function is independent of your database connection!
     *  So make sure your database connection and mb_* (Multibyte) extention are using the same encoding
     *  Setting both the connection and `mb_internal_encoding()` to UTF-8 is recommended!
     *
     *  WARNING: This is NOT the same as `PDO::quote`! This is more like mysqli::real_escape_string + quotes!
     *  `PDO` adds the weird `''` syntax to strings
     *
     *  @param  string $string The string you want to escape and quote
     *
     *  @return string
     */
    public static function quote($string)
    {
        return call_user_func(self::$quote_handler, $string);
    }


    private static function default_escape_string($string)
    {
        static $patterns     =  ['/[\x27\x22\x5C]/u', '/\x0A/u', '/\x0D/u', '/\x00/u', '/\x1A/u'];
        static $replacements =  ['\\\$0', '\n', '\r', '\0', '\Z'];

        return preg_replace($patterns, $replacements, $string); //  27 = ' 22 = " 5C = \ 1A = ctl-Z 00 = \0 (NUL) 0A = \n 0D = \r
    }


    private static function default_quote_string($string)
    {
        return self::$quot . self::escape($string) . self::$quot;
    }


    public function exec()
    {
        return call_user_func(self::$exec, $this->sql);
    }


    public function execute()
    {
        return call_user_func(self::$execute, $this->sql);
    }


    public function query()
    {
        return call_user_func(self::$query, $this->sql);
    }


    public function lookup()
    {
        return call_user_func(self::$lookup, $this->sql);
    }


    public function fetchAll()
    {
        return call_user_func(self::$fetchAll, $this->sql);
    }

    public function fetch_all()
    {
        return call_user_func(self::$fetchAll, $this->sql);
    }


    public function fetchAllAssoc()
    {
        return call_user_func(self::$fetchAll, $this->sql);
    }

    public function fetch_all_assoc()
    {
        return call_user_func(self::$fetchAll, $this->sql);
    }

    public function fetchAllNum()
    {
        return call_user_func(self::$fetchNum, $this->sql);
    }

    public function fetch_all_num()
    {
        return call_user_func(self::$fetchNum, $this->sql);
    }


    public function fetchAllIndexed($index = 'id')
    {
        return call_user_func(self::$fetchAllIndexed, $this->sql, $index);
    }

    public function fetch_all_indexed($index = 'id')
    {
        return call_user_func(self::$fetchAllIndexed, $this->sql, $index);
    }


    public function fetchArray()
    {
        return call_user_func(self::$fetchAll, $this->sql);
    }


    public function fetch_array()
    {
        return call_user_func(self::$fetchAll, $this->sql);
    }


    public function fetchAssoc()
    {
        return call_user_func(self::$fetchAll, $this->sql);
    }


    public function fetch_assoc()
    {
        return call_user_func(self::$fetchAll, $this->sql);
    }


    public function fetchNum()
    {
        return call_user_func(self::$fetchNum, $this->sql);
    }


    public function fetch_num()
    {
        return call_user_func(self::$fetchNum, $this->sql);
    }


    public static function noConnError()
    {
        throw new \Exception('No connection has been set, please use \\Twister\\Sql::setConnection($conn) (passing your `connection` variable) before calling this function!');
    }


    /**
     *  @todo Possible future addition ~ create a 'true' prepared statement wrapper
    public function realPrepare($stmt, ...$params)
    {
        return self::$prepare($this->sql);
    }


    public function real_prepare($stmt, ...$params)
    {
        return self::$prepare($this->sql);
    }
    */


    /**
     *  Registers a custom 'data type'
     *
     *  Custom data types are handled by `%type` syntax
     *  Allowing you to hook into the strings,
     *  handling the output of custom data types
     *
     *  @todo Explain this functionality further
     *
     *  @param string $type Type name eg. 'password', 'date' etc.
     *  @param string $func Callback function handling the type
     *
     *  @return void
     */
    public static function registerDataType($type, $func)
    {
        self::$types[$type] = $func;
    }


    /**
     *  Registers a custom 'modifier'
     *
     *  Modifiers are those that preceed the type eg. `%type:modifier`
     *
     *  @todo Explain this functionality further
     *
     *  @param string $modifier eg. 'password', 'hash', 'mydate'
     *  @param string $func Callback function handling the modifier
     *
     *  @return void
     */
    public static function registerModifier($modifier, $func)   //  should add the `position`, like `before`, `after` etc.
    {
        self::$modifiers[$modifier] = $func;
    }


    /**
     *  Removes unecessary formatting (like \t\r\n) from all statements
     *
     *  This statement effectively executes a:
     *      `preg_replace('/\s+/', ' ', ...)` on the internal reserved keywords
     *
     *  This is useful for generating statements for execution on the console.
     *
     *  Warning: This is a destructive statement, it applies to ALL statements
     *  constructed after this call, and there is NO reversing this effect!
     *
     *  @return void
     */
    public static function singleLineStatements()
    {
        self::$translations = array_map(function ($string) {
            return preg_replace('/\s+/', ' ', $string);
        }, self::$translations);
    }


    /**
     *  Converts all internal SQL statements like `SELECT` to lowercase `select`
     *
     *  This statement effectively executes a:
     *      `strtolower(...)` on the internal reserved keywords
     *
     *  This is just a convenient function for those that prefer lowercase SQL statements
     *
     *  Warning: This is a destructive statement, it applies to ALL statements
     *  constructed after this call, and there is NO reversing this effect!
     *
     *  @return void
     */
    public static function lowerCaseStatements()
    {
        self::$translations = array_map('strtolower', self::$translations);
    }
}
