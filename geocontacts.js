// geocontacts.js
// do not distribute without geocontacts.php

/*
Copyright 2007-2008 [Modern Success, Inc.](http://www.glutenenvy.com/)

Commercial users are requested to, but not required to, contribute promotion, 
know-how, or money to plug-in development or to www.glutenenvy.com. 

This is program is free software; you can redistribute it and/or modifyit under the terms of the GNU General Public License as published bythe Free Software Foundation; either version 2 of the License, or(at your option) any later version.This program is distributed in the hope that it will be useful,but WITHOUT ANY WARRANTY; without even the implied warranty ofMERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See theGNU General Public License for more details.You should have received a copy of the GNU General Public Licensealong with this program; if not, write to the Free SoftwareFoundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USAOnline: http://www.gnu.org/licenses/gpl.txt*/

var geocontacts_geocoder = new GClientGeocoder();
var geocontacts_map;
var geocontacts_marker;
var geocontacts_point;
var geocontacts_marker_exists=false;

function geocontacts_createmarker(lat,lon){

        if(geocontacts_marker_exists==true) {
                geocontacts_deletemarker();
        } 
        // we're making a new one below
        geocontacts_marker_exists=true;
        
        geocontacts_point = new GLatLng(parseFloat(lat),parseFloat(lon));

	geocontacts_marker = new GMarker(geocontacts_point);
        
	geocontacts_map.addOverlay(geocontacts_marker);

}

function geocontacts_deletemarker(){

	geocontacts_map.removeOverlay(geocontacts_marker);
	delete(geocontacts_marker);
        delete(geocontacts_point);
        geocontacts_marker_exists=false;

}

function geocontacts_savezoom() {
        zoom = parseInt(document.getElementById("zoom").value);
	geocontacts_map.setZoom(zoom);
}       


function geocontacts_geocode_finetune() {
  
	GEvent.addListener(geocontacts_map, 'zoomend', function() {
		// keep from zooming in the maximum 'map' level - other map types can be more detailed
		if (geocontacts_map.getZoom() <= 18) {
			document.getElementById("zoom").value = geocontacts_map.getZoom();
		}
	});

	GEvent.addListener(geocontacts_map, 'click', function(overlay,point) {
		if (point) {
			document.getElementById("lat").value=Math.round(point.lat()*1000000)/1000000;
			document.getElementById("lon").value=Math.round(point.lng()*1000000)/1000000;

			geocontacts_createmarker(point.lat(),point.lng());
			geocontacts_map.setCenter(geocontacts_point);
		}
    });
  
}

function click_contact(row, id) {
	var first_name=document.getElementById('first_name-'+id).value;
	var last_name=document.getElementById('last_name-'+id).value;
	var organization=document.getElementById('organization-'+id).value;
	var email=document.getElementById('email-'+id).value;
	var phone=document.getElementById('phone-'+id).value;
	var address_line1=document.getElementById('address_line1-'+id).value;
	var address_line2=document.getElementById('address_line2-'+id).value;
	var city=document.getElementById('city-'+id).value;
	var postcode=document.getElementById('postcode-'+id).value;
	var state=document.getElementById('state-'+id).value;
	var country=document.getElementById('country-'+id).value;
	var notes=document.getElementById('notes-'+id).value;
	var lat=document.getElementById('lat-'+id).value;
	var lon=document.getElementById('lon-'+id).value;
	var zoom=document.getElementById('zoom-'+id).value;
	var website=document.getElementById('website-'+id).value;

	document.getElementById('contact-info').innerHTML=document.getElementById('contact-'+id+'-info').innerHTML;

	document.getElementById('first_name').value=first_name;
	document.getElementById('last_name').value=last_name;
	document.getElementById('organization').value=organization;
	document.getElementById('email').value=email;
	document.getElementById('phone').value=phone;
	document.getElementById('address_line1').value=address_line1;
	document.getElementById('address_line2').value=address_line2;
	document.getElementById('city').value=city;
	document.getElementById('postcode').value=postcode;
	document.getElementById('state').value=state;
	document.getElementById('country').value=country;
	document.getElementById('notes').value=notes;
	document.getElementById('lat').value=lat;
	document.getElementById('lon').value=lon;
	document.getElementById('zoom').value=zoom;
	document.getElementById('website').value=website;
}

function geocontacts_round(num,decimals) {
	return Math.round(num*(10^decimals));
}

function geocontacts_buildaddress() {
        var addr="";
        if(document.getElementById("address_line1").value!="") {
                addr=document.getElementById("address_line1").value
        }
        if(document.getElementById("address_line2").value!="") {
                addr=addr+", "+document.getElementById("address_line2").value
        }
        if(document.getElementById("city").value!="") {
                addr=addr+", "+document.getElementById("city").value
        }
        if(document.getElementById("state").value!="") {
                addr=addr+", "+document.getElementById("state").value
        }
        if(document.getElementById("postcode").value!="") {
                addr=addr+" "+document.getElementById("postcode").value
        }
        if(document.getElementById("country").value!="") {
                addr=addr+", "+document.getElementById("country").value
        }
        return addr;      
}

function geocontacts_geocode () {
	var address = geocontacts_buildaddress();
	var zoom = document.getElementById("zoom").value;
	var lat;
	var lon;

	// Note FF needs the ID= to be set for getElementByID to work
  
	if (geocontacts_geocoder) {
		geocontacts_geocoder.getLatLng(address,
			function(point) {
				if (!point) {
					alert(address + " not found.\nTry querry directly on maps.google.com.");
				} else {
					// This will round to nearest 6 decimals
					//lat = Math.round(point.lat()*1000000)/1000000;
					//lon = Math.round(point.lng()*1000000)/1000000;
					// or take the default 
					lat = point.lat();
					lon = point.lng();
					
					//put lat and lon into form and give alert that it was found
					//document.getElementById("lat["+num+"]").value = lat;
					//document.getElementById("lon["+num+"]").value = lon;
					document.getElementById("lat").value = lat;
					document.getElementById("lon").value = lon;
					//alert(address + " found and coded");
					
					geocontacts_adjustmap(lat,lon,zoom);
//                                        geocontacts_geocode_finetune();
				}
			}
		);
	}
}

function geocontacts_adjustmap(lat,lon,zoom) {
	// Center the map on this point
	geocontacts_map.setCenter(geocontacts_point, parseInt(zoom), G_HYBRID_MAP);
	
	// Create a marker
	geocontacts_createmarker(lat,lon);
			
	// Center the map on this point
	geocontacts_map.setCenter(geocontacts_point);

}

function geocontacts_redrawmap() {
	// Create a marker
	geocontacts_createmarker(document.getElementById("lat").value,document.getElementById("lon").value);
			
	// Center the map on this point
	geocontacts_map.setCenter(geocontacts_point, parseInt(document.getElementById("zoom").value));
}


function geocontacts_showmap (lat, lon, zoom) {
        // decimal coordinates have a maximum
        // 90 to -90 for latitude (90 North Pole, -90 South Pole)
        // 180 -180 for longitude (180 -180 should be the same spot around)
        
        var mylat = parseFloat(lat);
        var mylon = parseFloat(lon);
        var rndlat = geocontacts_round(mylat,6);
        var rndlon = geocontacts_round(mylon,6);
        var maxlat = geocontacts_round(85,6);
        var minlat = -maxlat;
        var maxlon = geocontacts_round(180,6);
        var minlon = -maxlon;
      
        //lets keep it sanely in the google map range

        if(rndlat>maxlat) {
                mylat=85;
                zoom=2;
				document.getElementById("lat").value = mylat;
				document.getElementById("zoom").value = zoom;
        } else if(rndlat<minlat) {
                mylat=-85;
                zoom=2;
				document.getElementById("lat").value = mylat;
				document.getElementById("zoom").value = zoom;
        }
        
        if(rndlon>maxlon) {
                mylon=180;
                zoom=2;
                document.getElementById("lon").value = mylon;
				document.getElementById("zoom").value = zoom;
        } else if(rndlon<minlon) {
                mylon=-180;
                zoom=2;
                document.getElementById("lon").value = mylon;
				document.getElementById("zoom").value = zoom;
        }
    
	if (!GBrowserIsCompatible()) { 
		alert("Please try a different browser for Google maps.");
	}

	// Create new map object
	geocontacts_map = new GMap2(document.getElementById("map"));
	// create a point
	geocontacts_point = new GLatLng(parseFloat(mylat),parseFloat(mylon));	

	if (document.all&&window.attachEvent) { // IE-Win 
		window.attachEvent("onunload", GUnload); 
	} else if (window.addEventListener) { // Others 
		window.addEventListener("unload", GUnload, false); 
	} 

	geocontacts_map.addMapType(G_PHYSICAL_MAP);
	geocontacts_map.addControl(new GMapTypeControl(1)); 
	geocontacts_map.addControl(new GLargeMapControl()); 
	
	geocontacts_adjustmap(mylat,mylon,zoom);
	
	// safari browsers do not init like firefox and ie. 
	// redraw the map in a few seconds to get initial map display on safari
	setTimeout("geocontacts_redrawmap()",2000);	
}
