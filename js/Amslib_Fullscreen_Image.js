var Amslib_Fullscreen_Image = my.Amslib_Fullscreen_Image = my.Class(my.Amslib,
{
	container:	false,
	imageRatio:	false,
	resizeNode:	false,
	
	STATIC: {
		autoload: function(){
			$(Amslib_Fullscreen_Image.options.autoload).each(function(){
				new Amslib_Fullscreen_Image(this);
			});
		},
		
		options: {
			amslibName:	"Amslib_Fullscreen_Image",
			autoload:	".amslib_fullscreen_image.amslib_autoload",
			container:	".amslib_fullscreen_image_container",
			vertical:	"vertical",
			horizontal:	"horizontal"
		}
	},
	
	constructor: function(image,container){
		Amslib_Fullscreen_Image.Super.call(this,image,Amslib_Fullscreen_Image.options.amslibName);
		
		this.container = container || this.parent.closest(Amslib_Fullscreen_Image.options.container) || $(document.body);
		
		//	TODO: we need to implement the event system for this to work
		//this.observe("change-image",this.setImage.bind(this));
		this.setImage();
		
		this.enable();
	},
	
	enable: function()
	{
		if(!this.resizeNode)	this.resizeNode = $(document.onresize ? document : window);
		if(this.resizeNode)		this.resizeNode.on("resize",$.proxy(this,"resize"));
		
		this.resize();
		
		return this;
	},
	
	disable: function()
	{
		this.resizeNode.off("resize");
		
		return this;
	},
	
	setImage: function(parent)
	{
		parent = parent || this.parent;
		
		if(parent && parent.nodeType && parent.nodeName == "IMG"){
			this.parent		=	parent;
			this.imageRatio	=	this.parent.width() / this.parent.height();
		}
	},
	
	resize: function() {
		//	Sometimes, the image doesn't load until very late, causing the ratio calc to fail
		//	FIXME: this might cause a huge number of calls to a method which will fail everytime
		if(isNaN(this.imageRatio)) this.setImage();
		
		var rContainer	=	this.container.width() / this.container.height();
		
		this.parent
				.removeClass(Amslib_Fullscreen_Image.options.horizontal)
				.removeClass(Amslib_Fullscreen_Image.options.vertical);
		
		var c = (rContainer > this.imageRatio) ? Amslib_Fullscreen_Image.options.horizontal : Amslib_Fullscreen_Image.options.vertical;
		
		this.parent.addClass(c);
		
		//	NOTE: we need the new event system for this to be re-instated
		//this.callObserver("resize",this.parent,this.container,c);
	}
});

$(document).ready(Amslib_Fullscreen_Image.autoload);