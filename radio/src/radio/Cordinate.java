/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package radio;
import static java.lang.Math.*;
/**
 *
 * @author egidijus
 */
public class Cordinate {		
	public int deg;
	public int min;
	public int sec;
		
	public double rad;
	public double dec;
		
	public void Cordinate(int deg, int min, int sec) {
		this.deg = deg;
		this.min = min;
		this.sec = sec;			
	}
		
	/**
	* Converts DMS ( Degree / minute / second ) to decimal format longitude / latitude
        * @param deg degree
        * @param min minute
        * @param sec second
        * @return longitude / latitude in decimal format
	**/
	static double DMStoDEC(int deg, int min, int sec) {
           return deg + ((double)min*60+sec)/3600;
	}
        
        static short[] DECtoDMS(double dec) {
          
            short deg = (short) dec;
            double rem = dec-deg;
            rem *= 3600;
            short min = (short) (rem / 60);
            short sec = (short) (rem - (min*60));
            return new short[]{deg, min, sec};
	}	

}
