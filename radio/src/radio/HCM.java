/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package radio;

import java.io.File;
import java.io.IOException;
import java.io.RandomAccessFile;
import java.nio.ByteBuffer;
import java.nio.ByteOrder;
import java.nio.channels.FileChannel;
import java.security.InvalidParameterException;

/**
 * <code>
 * Lat lat = new Lat(55,12,0);
 * Lon lon =  new Lon(25,12,0);      
 * HCM.getElevation(lat, lon);
 * </code>
 */
public class HCM {
     
    final static String path = System.getProperty("path", "");
    
    final static String db_path = path+"/TOPO";
    
    final static String DS = "/";
    
    public static ByteBuffer[][] buffer = new ByteBuffer[90][180];
        
    public static double getElevation(Lat lat, Lon lon) {
        
        final byte lat_resolution = 3;
        byte lon_resolution;
            
        if(Math.abs(lat.deg) < 50) 
            lon_resolution = 3;
        else 
            lon_resolution = 6;
     
        if(lat.sec % lat_resolution != 0 && lon.sec % lon_resolution != 0) {
            /*
		[ P1 ] [ P2 ]
                      *
		[ P3 ] [ P4 ] 
            */
            
            int inf_lat = HCM.roundNum(lat.sec, lat_resolution);
            int sup_lat = inf_lat - lat_resolution;
            
            int inf_lon = HCM.roundNum(lon.sec, lon_resolution);
            int sup_lon = inf_lon - lon_resolution;
            
            Lat c1 = new Lat(lat.deg, lat.min, inf_lat);
            Lat c2 = new Lat(lat.deg, lat.min, sup_lat);
            Lon c3 = new Lon(lon.deg, lon.min, inf_lon);
            Lon c4 = new Lon(lon.deg, lon.min, sup_lon);
            
            double e1 = HCM.getElevation(c1, c3);
            double e2 = HCM.getElevation(c1, c4);
            double e3 = HCM.getElevation(c2, c3);
            double e4 = HCM.getElevation(c2, c4);
            
            double[][]  points = {
                {c2.dec, c4.dec, e4},
                {c2.dec, c3.dec, e3},
                {c1.dec, c4.dec, e2},
                {c1.dec, c3.dec, e1}
          
            };
            return HCM.bilinear_interpolation(lat.dec, lon.dec, points);
        }
        else if(lon.sec % lon_resolution != 0) {
            /**
            * [P1] * [P2]
            **/
            int inf_lon = HCM.roundNum(lon.sec, lon_resolution);
            int sup_lon = inf_lon - lon_resolution;
					
            Lon c1 = new Lon(lon.deg, lon.min, sup_lon);
            Lon c2 = new Lon(lon.deg, lon.min, inf_lon);

            double e1 = HCM.getElevation(lat, c1);
            double e2 = HCM.getElevation(lat, c2);

            return HCM.linear_interpolation(c1.sec, lon.sec, c2.sec, e1, e2);
        }
        else if(lat.sec % lat_resolution != 0) {
            /**
            [ P1 ] 
             *
            [ P2 ]
            **/	
            int inf_lat = HCM.roundNum(lat.sec, lat_resolution);
            int sup_lat = inf_lat - lat_resolution;
			
            Lat c1 = new Lat(lat.deg, lat.min, sup_lat);
            Lat c2 = new Lat(lat.deg, lat.min, inf_lat);
			
            double e1 = HCM.getElevation(c1, lon);
            double e2 = HCM.getElevation(c2, lon);
			
            return linear_interpolation(c1.sec, lat.sec, c2.sec, e1, e2);
    
        }
        else {
            
            char lat_prefix;
            char lon_prefix;
       
            if(lat.deg >= 0)
                lat_prefix = 'N';
            else
                lat_prefix = 'S';
        
            if(lon.deg >= 0)
                lon_prefix = 'E';
            else
                lon_prefix = 'W';
            
            short record_size;
            if(lon_resolution == 3)
                record_size = 20402;
            else
                record_size = 10302;
            
            
            int record_id = HCM.findRecordID(lat.min, lon.min);
            int point_id = HCM.findPointID(lat, lon, lon_resolution);
            
            int record_offset = HCM.offset(record_id, record_size);
            int point_offset = HCM.offset(point_id, 2);
            int OFFSET = record_offset + point_offset;
            
            try {
                if(buffer[lon.deg][lat.deg] == null) {

                    String x_0 = String.format("%03d", lon.deg);
                    String y_0 = String.format("%02d", lat.deg);

                    String file_path = db_path + DS + lon_prefix + x_0 + DS + lon_prefix 
                            + x_0 + lat_prefix + y_0+"."+ lon_resolution + lat_resolution + "E";

                    File file = new File(file_path);
                    FileChannel readOnlyChannel = new RandomAccessFile(file, "r").getChannel();

                    buffer[lon.deg][lat.deg] = readOnlyChannel.map(FileChannel.MapMode.READ_ONLY, 0, (int) readOnlyChannel.size());
                    buffer[lon.deg][lat.deg].order(ByteOrder.LITTLE_ENDIAN);
                }
              
                return buffer[lon.deg][lat.deg].getShort(OFFSET);
            } catch (IOException e) {
                System.out.println("IOException:");
                e.printStackTrace();
              }
            return 0;
        }        
    }
    
    private static int findPointID(Lat lat, Lon lon, int resolution) {
        int LON_SEC_INDEX = (lon.sec/resolution)+1;
			
	int LON_MIN_INDEX = lon.min % 5;
	
        int LON_INDEX;
	if(resolution == 3 )
		LON_INDEX = LON_MIN_INDEX * 20 + LON_SEC_INDEX;
	else
		LON_INDEX = LON_MIN_INDEX * 10 + LON_SEC_INDEX;
	
		/*---------------*/
		
	int LAT_MIN_INDEX = lat.min % 5;
	
	int LAT_SEC_INDEX = (lat.sec)/3;

        int LAT_INDEX;
	if(resolution == 3) 
            LAT_INDEX = ((LAT_MIN_INDEX * 20) + LAT_SEC_INDEX) * 101;
	else
            LAT_INDEX = ((LAT_MIN_INDEX * 20) + (LAT_SEC_INDEX)) * 51;
	
        int INDEX;
	INDEX = LON_INDEX +  LAT_INDEX;       
	return INDEX;
        
    }
    
    private static double bilinear_interpolation(double x, double y, double[][] points) {
       
        double x1 = points[0][0];
        double y1 = points[0][1];
        double q11 = points[0][2];
        
        double _x1 = points[1][0];
        double y2 = points[1][1];
        double q12 = points[1][2];
      
        double x2 = points[2][0];
        double _y1 = points[2][1];
        double q21 = points[2][2];
        
        double _x2 = points[3][0];
        double _y2 = points[3][1];
        double q22 = points[3][2];


	if(x1 != _x1 || x2 != _x2 || y1 != _y1 || y2 != _y2) {
            throw new InvalidParameterException("Points do not form a rectangle");
	}
		
	if(!(x1 <= x && x <= x2) || !(y1 <= y && y <= y2)) {
		throw new InvalidParameterException("(x, y) not within the rectangle");
	}
			
	return (q11 * (x2 - x) * (y2 - y) +
            q21 * (x - x1) * (y2 - y) +
            q12 * (x2 - x) * (y - y1) +
            q22 * (x - x1) * (y - y1)
           ) / ((x2 - x1) * (y2 - y1));
	
    }
    
    private static int findRecordID(int lat, int lon) {
        int lat_to = HCM.roundNum(lat, 5);
        int lat_from = lat_to - 5;
        
        int lon_to = HCM.roundNum(lon, 5);
        int lon_from = lon_to - 5;
        
        int record_number = (lat_from/5) * 12  + (lon_to/5);
	if(lon !=0 && lon % 5 == 0) {
            record_number++;
	}
			
	if(lat != 0 && lat % 5 == 0) {
            record_number += 12;
	}
		
	return record_number;
    }
    
    public static int roundNum(double num, int nearest) {         
        if(num == 0)
            return nearest;
        else
            return (int) (Math.ceil(num / nearest) * nearest);
         
    }
    
    public static int offset(int record_number, int size) {
        return  (record_number-1)*size;
    }
    
    private static double linear_interpolation(double x1, double x2, double x3, 
            double y1, double y3) {
	return (((x2-x1)*(y3-y1))/(x3-x1))+y1;
    }
}