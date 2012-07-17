/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package radio;
import static java.lang.Math.atan;
import static java.lang.Math.toDegrees;
/**
 *
 * @author egidijus
 */
public class Terrain {
    	/**
	* In telecommunication, the term effective height can refer to the height of the center of 
	* radiation of an antenna above the effective ground level P.1546, 3 annex. 
	* Returns effective height of transmitter. Test with http://www.itu.int/SRTM3/
	* @var point
	* @var azimuth
        * @var d
        * @var ha
	* @return effective height h1
	**/
        public static double effectiveHeight(Point point, double azimuth, double d, double ha) {
	
            //1 Get elevation level of ground where antenna is located
            double h = HCM.getElevation(point.lat, point.lon);
           
            //2 Calculate avarage ground level around antenna
            double tmp = 0;
            double avg_h = 0;
		
            // For shorter distance than 15km
            if(d < 15) {
		int iterations_count = 0;
                    for(double j = 0.2 * d; j < d ; j += 0.5 ) {
			iterations_count++;
			Point p = point.calculateOffset(j, azimuth);
			tmp += HCM.getElevation(p.lat, p.lon);
                        avg_h =  tmp/iterations_count;
                    }
		}
		// For longer distance 15km or longer
		else {
                    int iterations_count = 0;
                    for(double j = 3; j <= 15; j += 0.5 ) {
                        iterations_count++;
			Point p = point.calculateOffset(j, azimuth);
			tmp += HCM.getElevation(p.lat, p.lon);
			avg_h =  tmp/iterations_count;
                    }
		}
		return h - avg_h + ha;
	}
        
        public static double clearanceAngle(Point point, double azimuth, double d, double h2) {
            
            // Getting elevation level of ground where antenna is located
            double h = h2 + HCM.getElevation(point.lat, point.lon);
            
            double highest = 0;
            double tca;
                
            if(d > 16) {
                d = 16;
            }
		
            // Step 0.25km
            for(double j = 0.25; j <= d; j += 0.5 ) {
                Point p = point.calculateOffset(j, azimuth);
			
		double ground_h = HCM.getElevation(p.lat, p.lon);
                       
                if(ground_h > h) {
                    highest = ground_h;
                }
            }
		
            // In case visibility is clear
            if(highest == 0 ) 
                tca = 0;
            else 
                tca = toDegrees(atan((highest-h)/d));
		
            return tca;
        }
}