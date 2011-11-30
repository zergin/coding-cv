<?php
	namespace nefr\core;

	class OrderedQueue implements \Iterator, \Countable, \ArrayAccess, \Serializable {
		protected $_data = null;
		protected $_map = array();

		public function __construct() {
			$this->_data = new \SplObjectStorage();
		}

		public function attach($object,$metadata=null) {
			if(!is_object($object))
				throw new \InvalidArgumentException('OrderedQueue can contain only objects.');

			if($this->_data->contains($object))
				throw new \InvalidArgumentException('OrderedQueue can contain only unique objects.');

			// get next free position and get reference to last...
			if(is_null($last=$this->lastKey())) {
				$next = 0;

			} else {
				$next = $last + 1;
				$last = $this->_map[$last];

				assert('is_object($last)');							# check if last is an object
				assert('null == $this->_data[$last]["next"]');					# check if next of last is null (should be)
			}

			// sanity checks...
			assert('!array_key_exists($next,$this->_map)');						# check if new key is free

			// add new object and it's queue references...
			$this->_map[$next] = $object;
			$this->_data[$object] = array(
				'position'	=> $next,
				'next'		=> null,
				'prev'		=> $last,
				'metadata'	=> $metadata
			);

			// last's next is now our new object...
			is_object($last) and $this->_data[$last] = array_merge($this->_data[$last],array('next'=>$object));

			/* no need for array sort here (like there is in insertBefore()) because new element in array
			   (last internal pointer position) will have max+1 key value, that is last; array order is preserved */

			// sanity checks...
			assert('count($this->_map) == count($this->_data)');				# check if total count for both storages is correct
			assert('$this->_map[$this->_data[$object]["position"]] == $object');		# check if position of $object is consistent in both storages

			return $this;
		}

		public function detach($object) {
			if(!is_object($object))
				throw new \InvalidArgumentException('OrderedQueue can contain only objects.');

			if(!$this->_data->contains($object))
				throw new \InvalidArgumentException('Can\'t remove an object that is not in the queue');

			$position = $this->_data[$object]['position'];

			// remove queue referencess...
			$prev = $this->_data[$object]['prev'];
			$next = $this->_data[$object]['next'];

			if(is_object($next))
				$this->_data[$next] = array_merge($this->_data[$next],array('prev'=>$prev));

			if(is_object($prev))
				$this->_data[$prev] = array_merge($this->_data[$prev],array('next'=>$next));

			// remove element...
			unset($this->_map[$position]);
			unset($this->_data[$object]);

			/* no need for array sort here (like there is in insertBefore()) because order hasn't changed: we just
			   have one element less. */

			// sanity checks...
			assert('count($this->_map) == count($this->_data)');				# check if total count for both storages is correct
			assert('!isset($this->_map[$position])');							# check if $this->_map doesn't contain removed object
			assert('!$this->_data->contains($object)');							# check if $this->_data doesn't contain removed object

			return $this;
		}

		public function insertBefore($new,$before,$metadata=null) {
			// renumber objects (considering non sequential numbering)...
			$stating = $this->_data[$before]['position'];

			// 1st: reverse array (preserving keys) as we must proceed backwards
			$this->_map = array_reverse($this->_map,true);

			// 2nd: go through array updating numbers by one only for $before and following
			foreach($this->_map as $position => $object) {
				if($position < $stating) continue;

				$this->_map[$position+1] = $this->_map[$position];
				$this->_data[$object] = array_merge($this->_data[$object],array('position'=>$position+1));
			}

			// 3rd: restore initial order...
			$this->_map = array_reverse($this->_map,true);

			// 4th: update queue references
			$prev = $this->_data[$before]['prev'];
			$next = $this->_data[$before]['next'];

			$this->_data[$before] = array_merge($this->_data[$before],array('prev'=>$new));
			is_object($prev) and $this->_data[$prev] = array_merge($this->_data[$prev],array('next'=>$new));

			// 5th: insert new object...
			$this->_map[$stating] = $new;
			$this->_data[$new] = array(
				'position'	=> $stating,
				'next'		=> $before,
				'prev'		=> $prev,
				'metadata'	=> $metadata
			);

			// sort array by keys so internal position of array corresponds to queue position...
			ksort($this->_map,SORT_NUMERIC);

			// sanity checks: insertion is fine...
			assert('count($this->_map) == count($this->_data)');					# check if total count for both storages is correct
			assert('$this->_data->contains($new)');									# check if element was added...
			assert('$this->_map[$this->_data[$new]["position"]] == $new');						# check if position of $object is consistent in both storages
			assert('$this->_map[$this->_data[$before]["position"]] == $before');				# check if position of $before is consistent in both storages
			assert('$this->_map[$this->_data[$this->findPrev($before)]["position"]] == $new');	# check if position of $object is truly before $before

			return $this;
		}

		public function insertAfter($new,$after,$metadata=null) {
			// if we want to isert after last object: just attach...
			if($this->isLast($after)) return $this->attach($new,$metadata);

			// ...otherwise find next object and insert before...
			else return $this->insertBefore($new,$this->findNext($after),$metadata);
		}

		/**	Function returns 1st element in queue
		  *
		  *	This function returns 1st array element in queue; by default reseting container internal pointer.
		  *	This is important when using while iterating over queue as the pointer common for both operations.
		  *
		  *	This function can work w/o changing the internal pointer of mapping array, however it will have to iterate
		  *	over all contained objects to find first (one that has no 'prev' reference)
		  */
		public function first($noreset=false) {
			// if no elements return null
			if(!count($this->_map)) return null;

			if($noreset) {
				foreach($this->_data as $object => $meta) {
					if($meta['prev']==null) return $obejct;
				}

			} else return reset($this->_map);
		}

		public function isFirst($object) {
			return ($object === $this->first());
		}

		protected function firstKey($noreset=false) {
			// if no elements return null
			if(!count($this->_map)) return null;

			return $this->_data[$this->first($noreset)]['position'];
		}

		/**	Function returns last element in queue
		  *
		  *	This function returns last array element in queue
		  */
		public function last() {
			// if no elements return false
			if(!count($this->_map)) return null;

			return end($this->_map);
		}

		public function isLast($object) {
			return ($object === $this->last());
		}

		protected function lastKey() {
			// if no elements return null
			if(!count($this->_map)) return null;

			return $this->_data[end($this->_map)]['position'];
		}

		/**	Function returns next element in queue after given element
		  *
		  * Function returns next element in queue after given element; this function resets internal pointer
		  *
		  */
		public function findNext($object) {
			return $this->_data[$object]['next'];
		}

		/**	Function returns previous element in queue before given element
		  *
		  * Function returns previous element in queue before given element; this function resets internal pointer
		  *
		  */
		public function findPrev($object) {
			return $this->_data[$object]['prev'];
		}

		/* metadata handling... */
		public function getMetadata($object) {
			return $this->_data[$object]['metadata'];
		}

		public function setMetadata($object,$metadata=null) {
			$this->_data[$object] = array_merge($this->_data[$object],array('metadata'=>$metadata));
			return $this;
		}

		public function position($object) {
			return $this->_data[$object]['position'];
		}

		/* ArrayAccess: used for direct 'by position' access (both get and set) */
		public function offsetExists($position) {
			return isset($this->_map[$position]);
		}

		public function offsetGet($position) {
			return $this->_map[$position];
		}

		public function offsetSet($position,$object) {
			/* when no position ($OrderedQueue[] = $object) - append object... */
			if(is_null($position)) return $this->attach($object);

			/* if position is already taken: move all elements up queue and set new object for this position */
			if($this->offsetExists($position)) return $this->insertBefore($object,$this->_map[$position]);

			if(is_null($last = $this->lastKey())) {
				// null last key means the list is empty and we're inserting first element
				$next = null;
				$prev = null;

			} else {
				// if our index is higher then last then we can just set prev to last and next to null because we'll be the last one
				if($position > $last) {
					$prev = $this->_map[$last];
					$this->_data[$prev] = array_merge($this->_data[$prev],array('next'=>$object));

					$next = null;

				// otherwise we have to find prevous and next element manually
				} else {
					reset($this->_map);

					// advance array pointer to first key bigger then $position
					while(key($this->_map) < $position) next($this->_map);

					$next = current($this->_map);
					$prev = prev($this->_map);
				}
			}

			/* if position doesn't exist: insert object into it  */
			$this->_map[$position] = $object;
			$this->_data[$object] = array(
				'position'	=> $position,
				'next'		=> $next,
				'prev'		=> $prev,
				'metadata'	=> null
			);

			// sort array by keys so internal position of array corresponds to queue position...
			ksort($this->_map,SORT_NUMERIC);

			// sanity checks...
			assert('count($this->_map) == count($this->_data)');					# check if total count for both storages is correct
			assert('$this->_map[$position] == $object');							# check if object is in the right position
			assert('$this->_map[$this->_data[$object]["position"]] == $object');	# check if position of $object is consistent in both storages

			return $this;
		}

		public function offsetUnset($position) {
			return $this->detach($this->_map[$position]);
		}

		/* forward SplObjectStorage calls */
		public function contains($object) { return $this->_data->contains($object); }

		/* Iterator: always iterates in order because $this->_map has only numeric keys corresponding to object's position in queue */
		public function current() { return current($this->_map); }
		public function next() { return next($this->_map); }
		public function key() { return key($this->_map); }
		public function valid() { return (boolean) current($this->_map); }
		public function rewind() { return reset($this->_map); }

		/* Countable */
		public function count() { return count($this->_data); }

		/* Maintenance functions */

		/**	OrderedQueue Garbage Colector: resets map indexes
		  *
		  *	Many operations on ordered queue (especialy deletions and direct inserts)
		  * will result in non sequential numbering (two element queue may have indexes
		  * '2' and '16' for instance).
		  *
		  * This is correct and will work fine but it's sometimes undesired for algorythms that
		  * assume sequential, consistent numbering. You can reset indexes by calling this garbage colletor.
		  *
		  * This functions resets indexes by creating new map with array_values()
		  * and then updating references in SplObjectStorage.
		  */
		final public function gc() {
			// reset map indexes
			$this->_map = array_values($this->_map);

			// update info in SplObjectStorage
			foreach($this->_map as $position => $object)
				$this->_data[$object] = array_merge($this->_data[$object],array('position'=>$position));

			return $this;
		}


		/**
		  *	implement our own serialization because native serialization just suxx at everything
		  *	@see http://bugs.php.net/bug.php?id=49263
		  */

		public function serialize() {
			$intermediate = array();

			foreach($this->_map as $position => $object) {
				$info = $this->_data[$object];

				assert('$info["position"] == $position');

				$intermediate[] = array(
					'position'	=> $position,
					'metadata'	=> $info['metadata'],
					'object'	=> $object
				);
			}

			return serialize($intermediate);
		}

		public function unserialize($serialized) {
			$intermediate = unserialize($serialized);

			$this->_map  = array();
			$this->_data = new \SplObjectStorage;

			// return when nothing stored in serialized object
			if(empty($intermediate)) return;

			// get last key
			$last = count($intermediate) - 1;

			// sanity checks
			assert('array_key_exists($last,$intermediate)');
			assert('!array_key_exists(($last+1),$intermediate)');


			// rebuild next/prev references -- $index will be in order, w/o gaps integer keys; $position will be
			// position in queue.
			foreach($intermediate as $index => $data) {

				// sanity checks
				assert('is_object($data["object"])');
				assert('array_key_exists("metadata",$data)');
				assert('array_key_exists("position",$data)');

				// rebuild info array
				$info = array(
					'position'	=> $data['position'],
					'metadata'	=> $data['metadata'],
					'next'		=> ($last==$index ? null : $intermediate[$index+1]['object']),
					'prev'		=> (0==$index ? null : $intermediate[$index-1]['object'])
				);

				// assign in object
				$this->_map[$data['position']] = $data['object'];
				$this->_data[$data['object']] = $info;
			}

			// assert all data is equally in
			assert('count($this->_map) == count($this->_data)');

			// clean
			reset($this->_map);
			$this->_data->rewind();
		}
	}

?>
