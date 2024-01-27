var mapLocation = new google.maps.LatLng(46.200, -72.3400); //change coordinates here
var marker;
var map;

function initialize() {
    var mapOptions = {
        zoom: 11, //change zoom here
        center: mapLocation,
        scrollwheel: false
    };
    
    map = new google.maps.Map(document.getElementById('map-canvas'),
    mapOptions);
    
    
    //change address details here
    var contentString = '<div class="map-info">' 
    + '<div class="map-title">' 
    + '<h3><img alt="" src="img/brand.png"></h3></div>' 
    + '<p class="map-address"><i class="fa fa-map-marker"></i><span class="text"><strong>100 Broadway</strong><br> New York, NY 10005, США</span><br><i class="fa fa-phone"></i><span class="text">604-788-1832</span><br><span class="map-email"><i class="fa fa-envelope"></i><span class="text">info@sitename.com</span></span></p>' 
    + '<p><a href="https://www.google.com/maps/place/851+6th+Ave,+New+York,+NY+10001,+USA/data=!4m2!3m1!1s0x89c259af44f80211:0xbd87d30d3c7da9d2?sa=X&amp;ei=KqAdVazxJMTkuQS9sIGIBQ&amp;aved=0CB0Q8gEwAA" target="_blank">Open on Google Maps</a></p></div>';
    
    
    var infowindow = new google.maps.InfoWindow({
        content: contentString,
    });
    

    marker = new google.maps.Marker({
        map: map,
        draggable: true,
        title: 'SuperShot', //change title here
    
        animation: google.maps.Animation.DROP,
        position: mapLocation
    });

    google.maps.event.addListener(marker, 'click', function() {
        infowindow.open(map, marker);
    });

}

if ($('#map-canvas').length) {
    google.maps.event.addDomListener(window, 'load', initialize);
}

