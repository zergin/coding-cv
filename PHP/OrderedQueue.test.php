<?php

	require_once 'PHPUnit/Framework.php';
	require_once __DIR__ . '/../phpunit.init.inc';

	use nefr\core\OrderedQueue;

	class OrderedQueueTest extends PHPUnit_Framework_TestCase {
		protected $oq = null;

		protected function setUp() {
			$this->oq = new OrderedQueue;
		}

		/* generic object tests... */
		public function testNewOrderedQueue_contains() {
			$this->assertFalse($this->oq->contains(new stdClass));
		}

		/* OrderedQueue Implements Countable */
		public function testOrderedQueueCountable_implementation() {
			$this->assertTrue($this->oq InstanceOf \Countable);
		}

		public function testOrderedQueueCountable_count() {
			$this->assertEquals(0,count($this->oq));
		}

		/* OrderedQueue Implements ArrayAccess */
		public function testOrderedQueueArrayAccess_implementation() {
			$this->assertTrue($this->oq InstanceOf \ArrayAccess);
		}

		public function testOrderedQueueArrayAccess_addEmpty() {
			$this->oq[] = new stdClass;
			$this->assertEquals(1,count($this->oq));
		}

		public function testOrderedQueueArrayAccess_addIndexedZero() {
			$this->oq[0] = new stdClass;
			$this->assertEquals(1,count($this->oq));
		}

		public function testOrderedQueueArrayAccess_addIndexedRandom() {
			$this->oq[rand(1,100)] = new stdClass;
			$this->assertEquals(1,count($this->oq));
		}

		public function testOrderedQueueArrayAccess_addTwoAppendCount() {
			$this->oq[] = new stdClass;
			$this->oq[] = new stdClass;
			$this->assertEquals(2,count($this->oq));
		}

		public function testOrderedQueueArrayAccess_addTwoIndexedCount() {
			$this->oq[0] = new stdClass;
			$this->oq[1] = new stdClass;
			$this->assertEquals(2,count($this->oq));
		}

		public function testOrderedQueueArrayAccess_addTwoIndexedPrev() {
			$this->oq[0] = new stdClass;
			$this->oq[1] = new stdClass;
			$this->assertSame($this->oq->findPrev($this->oq[1]), $this->oq[0]);
		}

		public function testOrderedQueueArrayAccess_addTwoIndexConflictPrev() {
			$this->oq[0] = $o1 = new stdClass;
			$this->oq[0] = $o2 = new stdClass;
			$this->assertSame($this->oq->findPrev($o1), $o2);
		}

		public function testOrderedQueueArrayAccess_addTwoIndexedNext() {
			$this->oq[0] = new stdClass;
			$this->oq[1] = new stdClass;
			$this->assertSame($this->oq->findNext($this->oq[0]), $this->oq[1]);
		}

		public function testOrderedQueueArrayAccess_addTwoIndexConflictNext() {
			$this->oq[0] = $o1 = new stdClass;
			$this->oq[0] = $o2 = new stdClass;
			$this->assertSame($this->oq->findNext($o2), $o1);
		}

		public function testOrderedQueueArrayAccess_unsetReal() {
			$this->oq[0] = new stdClass;
			$this->oq[1] = new stdClass;

			unset($this->oq[0]);

			$this->assertEquals(1,count($this->oq));
		}

		public function testOrderedQueueArrayAccess_unsetNonExistant() {
			// unset() should trigger E_ERROR which is converted by PHPUnit to PHPUnit_Framework_Error Exception
			$this->setExpectedException('PHPUnit_Framework_Error');

			$this->oq[0] = new stdClass;
			unset($this->oq[1]);
		}

		public function testOrderedQueueArrayAccess_issetReal() {
			$this->oq[0] = new stdClass;
			$this->assertTrue(isset($this->oq[0]));
		}

		public function testOrderedQueueArrayAccess_issetNonExistant() {
			$this->oq[0] = new stdClass;
			$this->assertFalse(isset($this->oq[1]));
		}

		public function testOrderedQueueArrayAccess_nonSequentialAddOneNext() {
			$this->oq[7] = new stdClass;
			$this->assertNull($this->oq->findNext($this->oq[7]));
		}

		public function testOrderedQueueArrayAccess_nonSequentialAddOnePrev() {
			$this->oq[7] = new stdClass;
			$this->assertNull($this->oq->findPrev($this->oq[7]));
		}

		public function testOrderedQueueArrayAccess_nonSequentialAddTwoNext() {
			$this->oq[4] = new stdClass;
			$this->oq[7] = new stdClass;

			$this->assertSame($this->oq->findNext($this->oq[4]), $this->oq[7]);
		}

		public function testOrderedQueueArrayAccess_nonSequentialAddTwoPrev() {
			$this->oq[4] = new stdClass;
			$this->oq[7] = new stdClass;

			$this->assertSame($this->oq->findPrev($this->oq[7]), $this->oq[4]);
		}

		public function testOrderedQueueArrayAccess_sorted() {
			$this->oq[1] = $o1 = new stdClass;
			$this->oq[6] = $o3 = new stdClass;
			$this->oq[4] = $o2 = new stdClass;

			$count = 0;

			foreach($this->oq as $key => $current) {
				$ref = 'o' . ++$count;
				$this->assertSame($current, $$ref,"OrderedQueue sort ($key) doesn't match requested order ($count)");
			}
		}

		/** this tests both: possibilty to for(;;) through OrderedQueue and wether default indexing is consistent
		  *	with PHP internal array indexing.
		  */
		public function testOrderedQueueArrayAccess_for() {
			$o = array();

			// add objects...
			$this->oq[] = $o[] = new stdClass;
			$this->oq[] = $o[] = new stdClass;
			$this->oq[] = $o[] = new stdClass;
			$this->oq[] = $o[] = new stdClass;

			// assertions
			for($i=0;$i<4;$i++) $this->assertSame($o[$i], $this->oq[$i]);
		}

		/* OrderedQueue Implements Iterator */
		public function testOrderedQueueIterator_implementation() {
			$this->assertTrue($this->oq InstanceOf \Iterator);
		}

		/** this tests both: possibilty to for(;;) through OrderedQueue and whether iterator indexes match
		  * array access indexes
		  */
		public function testOrderedQueueIterator_foreach() {
			// add objects...
			$this->oq[] = new stdClass; $this->oq[] = new stdClass;
			$this->oq[] = new stdClass; $this->oq[] = new stdClass;

			// assertions
			foreach($this->oq as $key => $object) $this->assertSame($this->oq[$key], $object);
		}

		/* OrderedQueue::attach() */
		public function testAddScalarToQueueByAttach_argumentException() {
			$this->setExpectedException('InvalidArgumentException');
			$this->oq->attach(1);
		}

		public function testAddOneToQueueByAttach_count() {
			$this->oq->attach(new stdClass);
			$this->assertEquals(1,count($this->oq));
		}

		public function testAddOneToQueueByAttach_return() {
			$this->assertSame($this->oq->attach(new stdClass), $this->oq);
		}

		public function testAddOneToQueueByAttach_contains() {
			$this->oq->attach($o = new stdClass);
			$this->assertTrue($this->oq->contains($o));
		}

		public function testAddOneToQueueByAttach_position() {
			$this->oq->attach($o = new stdClass);
			$this->assertSame($this->oq[0], $o);
		}

		public function testAddOneToQueueByAttach_first() {
			$this->oq->attach($o = new stdClass);
			$this->assertSame($this->oq->first(), $o);
		}

		public function testAddOneToQueueByAttach_isFirst() {
			$this->oq->attach($o = new stdClass);
			$this->assertTrue($this->oq->isFirst($o));
		}

		public function testAddOneToQueueByAttach_last() {
			$this->oq->attach($o = new stdClass);
			$this->assertSame($this->oq->last(), $o);
		}

		public function testAddOneToQueueByAttach_isLast() {
			$this->oq->attach($o = new stdClass);
			$this->assertTrue($this->oq->isLast($o));
		}

		public function testAddTwoToQueueByAttach_count() {
			$this->oq->attach(new stdClass)->attach(new stdClass);
			$this->assertEquals(2,count($this->oq));
		}

		public function testAddTwoToQueueByAttach_uniq() {
			$o = new stdClass;

			try { $this->oq->attach($o)->attach($o); }
			catch (\InvalidArgumentException $e) { /* exception should be present but we ignore it */ }

			$this->assertEquals(1,count($this->oq));
		}

		public function testAddTwoToQueueByAttach_uniqException() {
			$o = new stdClass;

			$this->setExpectedException('InvalidArgumentException');
			$this->oq->attach($o)->attach($o);
		}

		public function testAddManyToQueueByAttach_count() {
			$num = rand(5,25);

			// add random number of elements to queue (between 5 and 25)
			for($i=0;$i<$num;$i++) $this->oq->attach(new stdClass);

			$this->assertEquals($num,count($this->oq));
		}

		/* OrderedQueue::detach() */
		public function testRemoveScalarFromQueueByDetach_argumentException() {
			$this->setExpectedException('InvalidArgumentException');
			$this->oq->detach(1);
		}

		public function testRemoveScalarFromQueueByDetach_nonExistantException() {
			$this->setExpectedException('InvalidArgumentException');
			$this->oq->detach(new stdClass);
		}

		public function testRemoveOneFromQueueByDetach_countOne() {
			$this->oq->attach($o = new stdClass)->detach($o);
			$this->assertEquals(0,count($this->oq));
		}

		public function testRemoveOneFromQueueByDetach_countTwo() {
			$this->oq->attach(new stdClass)->attach($o = new stdClass)->detach($o);
			$this->assertEquals(1,count($this->oq));
		}

		public function testRemoveOneFromQueueByDetach_return() {
			$return = $this->oq->attach($o = new stdClass)->detach($o);
			$this->assertSame($return, $this->oq);
		}

		public function testRemoveOneFromQueueByDetach_contains() {
			$this->oq->attach($o = new stdClass)->detach($o);
			$this->assertFalse($this->oq->contains($o));
		}

		public function testRemoveOneFromQueueByDetach_indexes() {
			$this->oq[5] = $o = new stdClass;
			$this->oq->detach($o);

			$this->assertFalse(isset($this->oq[5]));
		}

		/* OrderedQueue::setMetadata() */
		public function testSetMetadata_set() {
			$this->oq->attach($o = new stdClass)->setMetadata($o,true);
		}

		public function testSetMetadata_return() {
			$return = $this->oq->attach($o = new stdClass)->setMetadata($o,true);
			$this->assertSame($this->oq, $return);
		}

		/* OrderedQueue::getMetadata() */
		public function testGetMetadata_get() {
			$this->oq->attach($o = new stdClass)->setMetadata($o,true);
			$this->assertTrue($this->oq->getMetadata($o));
		}

		/* OrderedQueue::position() */
		public function testPosition_valid() {
			$this->oq
				->attach($o1 = new stdClass)
				->attach($o2 = new stdClass)
				->attach($o3 = new stdClass)
				->detach($o2)
				->attach($o4 = new stdClass);

			$this->assertEquals(0,$this->oq->position($o1));
			$this->assertEquals(2,$this->oq->position($o3));
			$this->assertEquals(3,$this->oq->position($o4));
		}

		public function testPosition_invalid() {
			$this->oq
				->attach($o1 = new stdClass)
				->detach($o1);

			$this->setExpectedException('UnexpectedValueException');
			$this->oq->position($o1);
		}

		/* OrderedQueue::insertBefore() */
		public function testAddOneToQueueByInsertBefore_count() {
			$this->oq->attach($o = new stdClass)->insertBefore(new stdClass, $o);
			$this->assertEquals(2,count($this->oq));
		}

		public function testAddOneToQueueByInsertBefore_return() {
			$return = $this->oq->attach($o = new stdClass)->insertBefore(new stdClass, $o);
			$this->assertSame($return, $this->oq);
		}

		public function testAddOneToQueueByInsertBefore_next() {
			$this->oq->attach($o1 = new stdClass)->insertBefore($o2 = new stdClass, $o1);
			$this->assertSame($this->oq->findNext($o2), $o1);
		}

		public function testAddOneToQueueByInsertBefore_prev() {
			$this->oq->attach($o1 = new stdClass)->insertBefore($o2 = new stdClass, $o1);
			$this->assertSame($this->oq->findPrev($o1), $o2);
		}

		public function testAddOneToQueueMiddleByInsertBefore_next() {
			$this->oq->attach($o1 = new stdClass)->attach(new stdClass)->insertBefore($o2 = new stdClass, $o1);
			$this->assertSame($this->oq->findNext($o2), $o1);
		}

		public function testAddOneToQueueMiddleByInsertBefore_prev() {
			$this->oq->attach($o1 = new stdClass)->attach(new stdClass)->insertBefore($o2 = new stdClass, $o1);
			$this->assertSame($this->oq->findPrev($o1), $o2);
		}

		public function testAddManyToQueueMiddleByInsertBefore_sorted() {
			$o1 = new stdClass;	$o2 = new stdClass;
			$o3 = new stdClass;	$o4 = new stdClass;

			$this->oq
				->attach($o2)
				->insertBefore($o1,$o2)
				->attach($o4)
				->insertBefore($o3,$o4);

			$count = 0;

			foreach($this->oq as $key => $current) {
				$ref = 'o' . ++$count;
				$this->assertSame($current, $$ref,"OrderedQueue sort ($key) doesn't match requested order ($count)");
			}
		}

		/* OrderedQueue::insertAfter() */
		public function testAddOneToQueueByInsertAfter_count() {
			$this->oq->attach($o = new stdClass)->insertAfter(new stdClass, $o);
			$this->assertEquals(2,count($this->oq));
		}

		public function testAddOneToQueueByInsertAfter_return() {
			$return = $this->oq->attach($o = new stdClass)->insertAfter(new stdClass, $o);
			$this->assertSame($return, $this->oq);
		}

		public function testAddOneToQueueByInsertAfter_next() {
			$this->oq->attach($o1 = new stdClass)->insertAfter($o2 = new stdClass, $o1);
			$this->assertSame($this->oq->findNext($o1), $o2);
		}

		public function testAddOneToQueueByInsertAfter_prev() {
			$this->oq->attach($o1 = new stdClass)->insertAfter($o2 = new stdClass, $o1);
			$this->assertSame($this->oq->findPrev($o2), $o1);
		}

		public function testAddOneToQueueMiddleByInsertAfter_next() {
			$this->oq->attach($o1 = new stdClass)->attach(new stdClass)->insertAfter($o2 = new stdClass, $o1);
			$this->assertSame($this->oq->findNext($o1), $o2);
		}

		public function testAddOneToQueueMiddleByInsertAfter_prev() {
			$this->oq->attach($o1 = new stdClass)->attach(new stdClass)->insertAfter($o2 = new stdClass, $o1);
			$this->assertSame($this->oq->findPrev($o2), $o1);
		}

		/* OrderedQueue::gc() */
		public function testOrderedQueueGC_return() {
			$this->assertSame($this->oq->gc(), $this->oq);
		}

		public function testOrderedQueueGC_count() {
			// add objects (inconsistent indexing)...
			$this->oq[1] = new stdClass; $this->oq[7] = new stdClass;
			$this->oq[7] = new stdClass; $this->oq[4] = new stdClass;

			// assertions
			$this->assertEquals($this->oq->count(),$this->oq->gc()->count());
		}

		public function testOrderedQueueGC_consistent() {
			// add objects (inconsistent indexing)...
			$this->oq[1] = new stdClass; $this->oq[7] = new stdClass;
			$this->oq[7] = new stdClass; $this->oq[4] = new stdClass;

			// run garbage collector...
			$this->oq->gc();

			// assertions
			for($i=0;$i<4;$i++) $this->assertTrue(isset($this->oq[$i]));
		}

		public function testOrderedQueueGC_sorted() {
			$this->oq[1] = $o1 = new stdClass;
			$this->oq[6] = $o3 = new stdClass;
			$this->oq[4] = $o2 = new stdClass;

			$count = 0;

			foreach($this->oq->gc() as $key => $current) {
				$ref = 'o' . ++$count;
				$this->assertSame($current, $$ref,"OrderedQueue sort ($key) doesn't match requested order ($count)");
			}
		}

		public function testAttachAfterSerialize() {
			$this->oq->attach($o1 = new \stdClass);
			$this->oq = unserialize(serialize($this->oq));

			$this->assertEquals(1,count($this->oq));
			$this->oq->attach($o2 = new \stdClass);
			$this->assertEquals(2,count($this->oq));
		}

		public function testDetachAfterSerialize() {
			$this->oq->attach(new \stdClass);
			$this->oq = unserialize(serialize($this->oq));

			$this->assertEquals(1,count($this->oq));
			$this->oq->detach($this->oq[0]);
			$this->assertEquals(0,count($this->oq));
		}

		public function testNextAfterSerialize() {
			$this->oq
				->attach((object) array('id' => 'o1'))
				->attach((object) array('id' => 'o2'));

			$this->oq = unserialize(serialize($this->oq));
			$this->assertEquals(2,count($this->oq));

			$o1 = $this->oq->first();
			$o2 = $this->oq->findNext($o1);

			$this->assertEquals('o1',$o1->id);
			$this->assertEquals('o2',$o2->id);
		}
	}

?>
