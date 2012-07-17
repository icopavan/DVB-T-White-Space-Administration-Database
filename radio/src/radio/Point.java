package radio;

import static java.lang.Math.*;

/**
 * Methods to manipulate whith geographic points.
 * @author Egidijus Kuzma
 * Based on {@link http://www.movable-type.co.uk/scripts/latlong.html}
 */
public class Point {
    
    public Lat lat;
    public Lon lon;
    
    static double R_cache[] = new double[90];
    
    public double R;
    
    final private double earthRadius = 6367; //radius in km
    final private double equatorRadius = 6378.137; //equatorial radius in km
    final private double polarRadius = 6356.7523142; //polar radius in km
    
    public Point(Lat lat, Lon lon) {
        this.lat = lat;
        this.lon = lon;
        if(R_cache[lat.deg] == 0) {
            
            this.R = getEarthRadius(lat.rad);
           
            R_cache[lat.deg] = this.R;
        }
        else {
            this.R = R_cache[lat.deg];
        }
    }
    
    /**
    * Returns earth radius at given latitude 
    * {@link http://rbrundritt.wordpress.com/2008/10/14/calculating-the-radius-of-the-earth/}
    * @param lat latitude value in radians
    * @return earth radius
    **/
    public double getEarthRadius(double lat){
        return equatorRadius*sqrt(pow(polarRadius,4)/pow(equatorRadius,4)*
            pow((Math.sin(lat)),2)+pow(cos(lat),2))/
            sqrt(1-(1-(polarRadius*polarRadius)/( equatorRadius*equatorRadius))
            *pow(sin(lat),2));
    }
   
    /**
     * Returns new point based on p1 from distance and azimuth
     * @param d distance between points
     * @param bearing angle beatween points in degrees
     * @return new point
     */
    public Point calculateOffset(double d, double bearing) {
	double brng = toRadians(bearing);
	double lat_rad = asin(sin(lat.rad)*cos(d/R) + cos(lat.rad)*sin(d/R)*cos(brng));
	double lon_rad = lon.rad + atan2((float)(sin(brng)*sin(d/R)*cos(lat.rad)), (float)(cos(d/R)-sin(lat.rad)*sin(lat_rad)));
        
	return new Point(new Lat(toDegrees(lat_rad)), new Lon(toDegrees(lon_rad)));
    }

    public double distance(Point p2) {
       return  acos(sin(lat.rad)*sin(p2.lat.rad) + cos(lat.rad)*cos(p2.lat.rad)*cos(p2.lon.rad-lon.rad)) * R;
    }
        
    public double getInitialBearing(Point finish)
    {
        double diff, term1, term2;

        double lon1 = lon.rad;
        double lat1 = lat.rad;
        double lon2 = finish.lon.rad;
        double lat2 = finish.lat.rad;

        diff = lon2 - lon1;
        term1 = Math.sin(diff);
        term2 = (Math.cos(lat1) * Math.tan(lat2)) - (Math.sin(lat1) * Math.cos(diff));
        return Math.toDegrees(Math.atan2(term1, term2));
    }
}
   