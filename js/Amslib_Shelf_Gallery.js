var Amslib_Shelf_Gallery = my.Amslib_Shelf_Gallery = my.Class(my.Amslib,
{
	items: false,
	mutex: false,
	
	STATIC: {
		autoload: function(){
			$(".amslib_shelf_gallery").each(function(){
				new Amslib_Shelf_Gallery($(this));
			});
		},
		
		options: {
			amslibName:		"Amslib_Shelf_Gallery",
			animate:		"animateAutoNext",
			selSlider:		".amslib_shelf_gallery_slider",
			selItem:		".amslib_shelf_gallery_item",
			normalTimeout:	5000,
			fastTimeout:	500
		}
	},
	
	constructor: function(parent)
	{
		Amslib_Shelf_Gallery.Super.call(this,parent,Amslib_Shelf_Gallery.options.amslibName);
		
		this.options	=	Amslib_Shelf_Gallery.options;
		this.slider		=	$(this.options.selSlider,this.parent);
		this.items		=	$(this.options.selItem,this.parent);
		this.mutex		=	false;
		
		this.start();
	},
	
	start: function()
	{
		this.timeout = setTimeout($.proxy(this,this.options.animate),this.options.normalTimeout);
	},
	
	stop: function()
	{
		if(this.timeout) clearTimeout(this.timeout);
	},
	
	setAnimation: function(type)
	{
		switch(type){
			case "animateAutoNext":
			case "animateAutoPrev":{
				this.options.animate = type;
			}break;
		}
	},
	
	prev: function(cb)
	{
		if(this.mutex) return;
		this.mutex = true;
		
		var first	=	$(this.options.selItem+":first",this.parent);
		var last	=	$(this.options.selItem+":last",this.parent);
		
		if(!last){
			this.mutex = false;
			return;
		}
		
		//	Move the last element to the first position, grab the left to offset by
		this.slider.prepend(last.detach());

		this.slider.css("left","-"+first.position().left+"px");
		//	Animate to left:0
		this.slider.animate({left:"0px"},this.options.normalTimeout*0.66,$.proxy(function(){
			if(cb) cb();
			
			this.mutex = false;
		},this));
	},
	
	next: function(cb)
	{
		if(this.mutex) return;
		this.mutex = true;
		
		var first = $(this.options.selItem+":first",this.parent);
		var next = first.next(this.options.selItem);

		//	If there is no next, return and don't do anything
		if(!next){
			this.mutex = false;
			return;
		}
		
		this.slider.animate({left:"-="+next.position().left},this.options.normalTimeout/2,$.proxy(function(){
			this.slider.append(first.detach());
			this.slider.css("left","0px");
			
			if(cb) cb();
			
			this.mutex = false;
		},this));
	},
	
	toIndex: function(index)
	{
		var first	=	$(this.options.selItem+":first",this.parent);
		var start	=	this.items.index(first);
		var finish	=	this.items.index(this.items.get(index));
		
		if(start == finish) return;
		
		if(start > finish){
			this.toIndexPrev(start,finish);
		}else{
			this.toIndexNext(start,finish);
		}
	},
	
	toIndexPrev: function(s,f)
	{
		if(this.mutex) return;
		this.mutex = true;
		
		for(a=s-1;a>=f;a--) this.slider.prepend($(this.items.get(a)).detach());
		
		var left = $(this.items.get(s)).position().left;
		this.slider.css("left","-"+left+"px");
		var time = (s-f)*this.options.fastTimeout;

		//	Animate to left:0
		this.slider.animate({left:"0px"},time,$.proxy(function(){
			//if(cb) cb();
			
			this.mutex = false;
		},this));
	},
	
	toIndexNext: function(s,f)
	{
		if(this.mutex) return;
		this.mutex = true;
		
		var end = $(this.items.get(f))
		var left = end.position().left;
		var time = (f-s)*this.options.fastTimeout;

		this.slider.animate({left:-left+"px"},time,$.proxy(function(){
			for(a=s;a<f;a++) this.slider.append($(this.items.get(a)).detach());
			this.slider.css("left","0px");

			//	NOTE: Why is this commented out? and what is cb anyway? callback for what?
			//if(cb) cb();
			
			this.mutex = false;
		},this));
	},
	
	animateAutoNext: function()
	{
		this.next($.proxy(this,"start"));
	},
	
	animateAutoPrev: function()
	{
		this.prev($.proxy(this,"start"));
	}
});

$(document).ready(Amslib_Shelf_Gallery.autoload);