 (function (document, window, $ ){
	
	// Feed Shell
	var fs = {
		shell: 			false,
		shell_width: 	0,
		total_width: 	0,
		index : 		0,
		total_index : 	0,
		feed_shell:		{}, 
		previous:		{}, 
		next:			{},     
	
		init: function( id ){
			
			fs.shell = $( id );
			
			fs.shell_width = fs.shell.width();
			fs.shell.find('.feed-slide').each(function(index, el){
				$(el).width(fs.shell_width);
				fs.total_width  = fs.total_width + fs.shell_width;
				fs.total_index =  index;
			}); // end of each
			
			fs.feed_shell = fs.shell.find('.feed-slider-shell');
			
			fs.previous = fs.shell.find('.previous-slide'); 
			fs.next     = fs.shell.find('.next-slide');
			fs.feed_shell.width( fs.total_width );
		
			
			fs.previous.click(function(){
				
				fs.index = fs.index - 1;
				if( fs.index < 0)
					fs.index = 0;
				
				fs.feed_shell.animate( { marginLeft: (-1) *fs.index * fs.shell_width }, 600 , function(){});
			
			})
			
			fs.next.click(function(){
				
				fs.index = fs.index + 1;
				
				if( fs.index > fs.total_index )
					fs.index = fs.total_index;
				
				fs.feed_shell.animate( { marginLeft: (-1) *fs.index * fs.shell_width }, 600 , function(){});
			
			});
		}
		
	}
 	// lets make it happen
 	$.each(feed_slider, function(index, value) {
		fs.init("#"+value);
	});

 })(document, window, jQuery)
 