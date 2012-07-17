/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package radio;

import java.security.InvalidParameterException;

/**
 *
 * @author egidijus
 */
public class Lat extends Cordinate {
    public Lat(int deg, int min, int sec) {
        if(!(-90 <= deg && deg <= 90)) {
           throw new InvalidParameterException("Invalid latitude degree value $deg given, should be -180 <= deg <= 180");
	}
		
        if(!(0 <= min && min < 60)) {
            throw new InvalidParameterException("Invalid lontitude minute value $min given, should be 0 <= min <= 59");
	}
		
	if(!(0 <= sec && sec <= 60)) {
            throw new InvalidParameterException("Invalid latitude second value $sec given, should be 0 <= sec <= 60");
	}
                      
	this.dec = super.DMStoDEC(deg, min, sec);
	this.rad = Math.toRadians(this.dec);
	super.Cordinate(deg, min, sec);	
     }
    
    public Lat(double dec) {
        if(!(-90 <= dec && dec <= 90)) {
            throw new InvalidParameterException("Invalid latitude given, should be -90 <= deg <= 90");
	}
	
	this.dec = dec;
	this.rad = Math.toRadians(dec);
	
        short[] c = DECtoDMS(dec);
        
	super.Cordinate(c[0], c[1], c[2]);	
    }
}