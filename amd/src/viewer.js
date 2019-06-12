define(['jquery'], function($) {
 var viewer = {};

 viewer.urltogo = null;

 viewer.init = function() {
 $(document).ready(function () {
 var newheight = ($(window).height()-50);
 if (newheight < 680 || isNaN(newheight)) {
  newheight = 680;
 }
 $("#content").height(newheight);
});

$(window).on('resize',function(){
 var newheight = ($(window).height()-50);
 if (newheight < 680 || isNaN(newheight)) {
  newheight = 680;
 }
 $("#content").height(newheight);
});
};

viewer.newwindow = function (urltogo) {
 setTimeout(function(){
 window.open(urltogo+'&win=1');}, 1500);
};
return viewer;
});
