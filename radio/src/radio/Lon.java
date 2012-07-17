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
public class Lon extends Cordinate {
    public Lon(int deg, int min, int sec) {
        this.dec = super.DMStoDEC(deg, min, sec);
        this.rad = Math.toRadians(this.dec);
        super.Cordinate(deg, min, sec);
    }
    
    public Lon(double dec) {
        if(!(-90 <= dec && dec <= 90)) {
            throw new InvalidParameterException("Invalid latitude given, should be -90 <= deg <= 90");
	}
	
	this.dec = dec;
	this.rad = Math.toRadians(dec);

        short[] c = DECtoDMS(dec);
	super.Cordinate(c[0], c[1], c[2]);
    }
}
