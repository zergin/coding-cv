var mQu = { cv: {} };

/**
 *  Simple flickr photo search.
 * 
 *  This module provides a simple flickr photo search object used to retreive 
 *  CreativeCommons "By" photos from flickr and insert thumbnails to a 
 *  predefined list.
 * 
 *  Example usage:
 *  
 *  <div id="target">
 *      <ul>
 *          <li><a rel="nofollow" href="#" title="Loading image...">Loading...</a></li>
 *          <li><a rel="nofollow" href="#" title="Loading image...">Loading...</a></li>
 *      </ul>
 *  </div>
 * 
 *  <script type="text/javascript">
 *      var mquFlickrPhotos = new mquFlickrPhotos(key, "#target");
 *          mquFlickrPhotos.search("flickr");
 *  </script>
 * 
 *  @version 1.0.0
 *  @author Marcin Kurzyna <mQu@proof-of-concept.eu>
 *  @license MIT
 * 
 *  Copyright (C) 2012 Marcin Kurzyna <mQu@proof-of-concept.eu>
 * 
 *  Permission is hereby granted, free of charge, to any person obtaining a copy 
 *  of this software and associated documentation files (the "Software"), to deal 
 *  in the Software without restriction, including without limitation the rights 
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
 *  copies of the Software, and to permit persons to whom the Software is 
 *  furnished to do so, subject to the following conditions:
 * 
 *  The above copyright notice and this permission notice shall be included in 
 *  all copies or substantial portions of the Software.
 *  
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, 
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE 
 * SOFTWARE.
 * 
 */
mQu.cv.flickr = (function mquFlickrPhotosGenerator() {
    var FlickrPhotos, 
        deferred = { ctx: window, func: null, args: [] }, 
        console;

    // debug compatibility
	if (!console) {
		console = { log: function() {}};
		console.debug = console.warn = console.debug = console.info = console.error = console.log;
	}

	// lazy load jQuery if not present
	if (typeof jQuery === "undefined") {
		var jQueryScript = document.createElement('script');
			jQueryScript.type = "text/javascript";
			jQueryScript.src = "http://code.jquery.com/jquery.min.js";					
			jQueryScript.async = "async";
			jQueryScript.onload = jQueryScript.onreadystatechange = function mquFlickrPhotosDeferredAfterJQuery () {

				if (typeof deferred.func !== 'function' || (this.readyState && !(this.readyState === 'loaded' || this.readyState === 'complete'))) {
					return;
				}
				
				deferred.func.apply(deferred.ctx, deferred.args);
				deferred = { ctx: window, func: null, args: []};
			};	

		document.body.appendChild(jQueryScript);
	}

	// private interface - functions used internaly only

	function mquFlickrPhotosSearchRunner(tags) {
		var me = this;

		jQuery.getJSON(
			"http://api.flickr.com/services/rest/?jsoncallback=?",
			{
				method: 'flickr.photos.search',
				api_key: this.key,
				tags: tags,
				license: 4,
				sort: "date-posted-desc",
				extras: "owner_name",
				format: "json",
				per_page: jQuery(this.container).find("li").size()
			},
			function (data, textStatus, jqxhr) {
				mquFlickrPhotosSearchDisplay.call(me, data, textStatus, jqxhr); 
			}
		);
	}

	function mquFlickrPhotosSearchDisplay (data, textStatus, jqxhr) {
		console.log("[mquFlickrPhotos] Flick search response", textStatus, data);

		if (!(typeof data === "object" && data.stat === "ok")) {
			return;
		}

		this.container = jQuery(this.container);
		
		this.container.find("li").each(function (i, e) {

			var anchor = jQuery(e).find("a");
				anchor.empty();						

			if (data.photos.photo[i]) {
				var img = document.createElement("img");
					img.src = "http://farm"+data.photos.photo[i].farm+".static.flickr.com/"+data.photos.photo[i].server+"/"+data.photos.photo[i].id+"_"+data.photos.photo[i].secret+"_s.jpg";
					img.alt = data.photos.photo[i].title + ' by ' + data.photos.photo[i].ownername;

				anchor.append(img);

				anchor.attr({
					href: "http://www.flickr.com/photos/"+data.photos.photo[i].owner+"/"+data.photos.photo[i].id,
					title: img.alt,
					target: '_blank'
				});
			}						
		});
	}


	// public interface - define the flickr search object

	FlickrPhotos = function mquFlickrPhotosConstructor(key, container) {
		this.key = key;
		this.container = container;
	};

	FlickrPhotos.prototype.search = function mquFlickrPhotosSearch(tags) {
		if (typeof jQuery !== "undefined") {
			mquFlickrPhotosSearchRunner.call(this, tags);
		} else {
			deferred.ctx = this;
			deferred.func = mquFlickrPhotosSearchRunner;
			deferred.args = [tags];
		}				
	};

	return FlickrPhotos;
}());

