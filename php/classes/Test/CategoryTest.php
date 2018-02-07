<?php
namespace Edu\Cnm\Kmaru\Test;

use Edu\Cnm\Kmaru\{Profile, Category};

//grab the class under scrutiny: Board
require_once(dirname(__DIR__) . "/autoload.php");

//grab the uuid generator
require_once(dirname(__DIR__, 2) . "/lib/uuid.php");

/**
 * Full PHPUnit text for the Category class. It is complete
 * because *ALL* mySQL/PDO enabled methods are tested for both
 * invalid and valid inputs.
 *
 * @see Board
 * @author Dylan McDonald <dmcdonald21@cnm.edu>
 * @author Anna Khamsamran <akhamsamran1@cnm.edu>
 **/
class CategoryTest extends KmaruTest {
	/**
	 * Profile that created the Category; this is for foreign key relations
	 * @var Profile profile
	 **/
	protected $profile = null;

	/**
	 * valid profile hash to create the profile object to own the test
	 * @var $VALID_HASH
	 **/
	protected $VALID_PROFILE_HASH;

	/**
	 * valid salt to use to create the profile object to own the test
	 * @var string $VALID_SALT
	 */
	protected $VALID_PROFILE_SALT;

	/**
	 * name of the Board
	 * @var string $VALID_CATEGORYNAME
	 **/
	protected $VALID_CATEGORYNAME = "PHPUnit test passing";

	/**
	 * name of the updated Category
	 * @var string $VALID_CATEGORYNAME2
	 **/
	protected $VALID_CATEGORYNAME2 = "PHPUnit test still passing";

	/**
	 * create dependent objects before running each test
	 **/
	public final function setUp() : void {
		// run the default setUp() method first
		parent::setUp();
		$password = "abc123";
		$this->VALID_PROFILE_SALT = bin2hex(random_bytes(32));
		$this->VALID_PROFILE_HASH = hash_pbkdf2("sha512", $password, $this->VALID_PROFILE_SALT, 262144);

		//create and insert a Profile to own this test Board
		$this->profile = new Profile(generateUuidV4(), null, "@handle", "test@phpunit.de", $this->VALID_PROFILE_HASH, "+12125551212", $this->VALID_PROFILE_SALT);
		$this->profile->insert($this->getPDO());
	}
	/**
	 * test inserting a valid Category and verify that the actual mySQL data matches
	 **/
	public function testInsertValidCategory() : void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("category");


		// create a new Category and insert to into mySQL
		$categoryId = generateUuidV4();
		$category = new Category($categoryId, $this->profile->getProfileId(), $this->VALID_CATEGORYNAME);
		$category->insert($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$pdoCategory = Category::getCategoryByCategoryId($this->getPDO(), $category->getCategoryId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("category"));
		$this->assertEquals($pdoCategory->getCategoryId(), $categoryId);
		$this->assertEquals($pdoCategory->getCategoryProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoCategory->getCategoryName(), $this->VALID_CATEGORYNAME);
	}
	/**
	 * test inserting a Category, editing it, and then updating it
	 **/
	public function testUpdateValidCategory() : void {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("category");

		//create a new Board and insert into mySQL
		$categoryId = generateUuidV4();
		$category = new Board($categoryId, $this->profile->getProfileId(), $this->VALID_CATEGORYNAME, $this->VALID_CATEGORYNAME);
		$category->insert($this->getPDO());

		//edit the Category and update it in mySQL
		$category->setCategoryName($this->VALID_CATEGORYNAME2);
		$category->update($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$pdoCategory = Category::getCategoryByCategoryId($this->getPDO(), $category->getCategoryId());
		$this->asssertEquals($pdoCategory->getCategoryId(), $categoryId);
		$this->assertEquals($numRows +1, $this->getConnection()->getRowCount("category"));
		$this->assertEquals($pdoCategory->getCategoryProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoCategory->getCategoryName(), $this->VALID_CATEGORYNAME2);
	}

	/**
	 * test creating a Category and then deleting it
	 **/
	public function testDeleteValidCategory() : void {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("category");

		//create a new Board and insert it into mySQL
		$boardId = generateUuidV4();
		$board = new Board($boardId, $this->profile->getBoardId(), $this->VALID_BOARDNAME);
		$board->insert($this->getPDO());

		//delete the Board from mySQL
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("board"));
		$this->delete($this->getPDO());

		//grab the data from mySQL and enforce the Board does not exist
		$pdoBoard = Board::getBoardByBoardId($this->getPDO(), $board->getBoardId());
		$this->assertNull($pdoBoard);
		$this->asserEquals($numRows, $this->getConnection()->getRowCount("board"));
	}

	/**
	 * test grabbing a Board that does not exist
	 **/
	public function testGetInvalidBoardByBoardId() : void {
		//grab a profile id that exceeds the maximum allowable profile id
		$board = Board::getBoardByBoardId($this->getPDO(), generateUuidV4());
		$this->assertNull($board);
	}

	/**
	 * test inserting a Board and re-grabbing it from mySQL
	 **/
	public function testGetValidBoardIdByBoardProfileId() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()>getRowCount("board");

		//create a new Board and insert it in to mySQL
		$boardId = generateUuidV4();
		$board = new Board($boardId, $this->profile->getProfileId());
		$board->insert($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$results = Board::getBoardByBoardProfileId($this->getPDO(), $board->getBoardProfileId());
		$this->assertEquals($numRows +1, $this->getConnection()->getRowCount("board"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\Kmaru\\Board", $results);

		//grab the result from the array and validate it
		$pdoBoard = $results[0];

		$this->assertEquals($pdoBoard->getBoardId(), $boardId);
		$this->assertEquals($pdoBoard->getBoardProfileId(), $boardId);
		$this->assertEquals($pdoBoard->getBoardContent(), $this->VALID_BOARDNAME);
	}

	/**
	 * test grabbing a Board that does not exist
	 **/
	public function testGetInvalidBoardByBoardProfileId(): void {
		// grab a profile id that exceeds the maximum allowable profile id
		$board = Board::getBoardByBoardProfileId($this->getPDO(), generateUuidV4());
		$this->assertCount(0, $board);
	}

	/**
	 * test grabbing a Board by board name
	 **/
	public function testGetValidBoardByBoardContent() : void {
		//count the number of rows and save for later
		$numRows = $this->getConnection()->getRowCount("board");

		//create a new Board and insert it into mySQL
		$boardId = generateUuidV4();
		$board = new Board($boardId, $this->profile->getProfileId(), $this->VALID_BOARDNAME);
		$board->insert($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$results = Board::getBoardByBoardName($this->getPDO(), $board->getBoardName());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("board"));
		$this->assertCount(1, $results);

		//enforce no other objects are bleeding into the test
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\Kmaru\\Board", $results);

		//grab the result from the array and validate it
		$pdoBoard = $results[0];
		$this->assertEquals($pdoBoard->getBoardId(), $boardId);
		$this->assertEquals($pdoBoard->getBoardProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoBoard->getBoardName(), $this->VALID_BOARDNAME);
	}

	/**
	 * test grabbing a Board by name that does not exist
	 **/
	public function testGetInvalidBoardByBoardName() : void {
		//grab a board by name that does not exist
		$board = Board::getBoardByBoardName($this->getPDO(), "Who is in the brig today?");
		$this->assertCount(0, $board);
	}

	/**
	 * test grabbing all Boards
	 **/
	public function testGetAllValidBoards(): void {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("board");

		//create a new Board and inster it into mySQL
		$boardId = generateUuidV4();
		$board = new Board($boardId, $this->profile->getProfileId(), $this->VALID_BOARDNAME);
		$board->insert($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$results = Board::getAllBoards($this->getPDO());
		$this->assertEquals($numRows +1, $this->getConnection()->getRowCount("board"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\Kmaru\\Board", $results);

		//grab the result from the array and validate it
		$pdoBoard = $results[0];
		$this->assertEquals($pdoBoard->getBoardId(), $boardId);
		$this->assertEquals($pdoBoard->getBoardProfileId(), $this->profile->getprofileId());
		$this->assertEquals($pdoBoard->getBoardName(), $this->VALID_BOARDNAME);
	}


}


?>