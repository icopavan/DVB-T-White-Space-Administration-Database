/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package radio;

       
import java.util.Arrays;


/**
 * <p>Static methods for doing useful math</p><hr>
 *
 * @author  : $Author: brian $
 * @version : $Revision: 1.1 $
 *
 * <hr><p><font size="-1" color="#336699"><a href="http://www.mbari.org">
 * The Monterey Bay Aquarium Research Institute (MBARI)</a> provides this
 * documentation and code &quot;as is&quot;, with no warranty, express or
 * implied, of its quality or consistency. It is provided without support and
 * without obligation on the part of MBARI to assist in its use, correction,
 * modification, or enhancement. This information should not be published or
 * distributed to third parties without specific written permission from
 * MBARI.</font></p><br>
 *
 * <font size="-1" color="#336699">Copyright 2002 MBARI.<br>
 * MBARI Proprietary Information. All rights reserved.</font><br><hr><br>
 *
 */

public class Utils{

    /**
     * Find the index of the value nearest to the key. The values array can
     * contain only unique values. If it doesn't the first occurence of a value
     * in the values array is the one used, subsequent duplicate are ignored.
     *
     * @param values Values to search through for the nearest point.
     * @param key The key to search for the nearest neighbor in values.
     * @return the index of the value nearest to the key.
     * @since 20011207
     */
    public static final int near(final double[] values, final double key) {

        int n = binarySearch(values, key);
        if (n < 0) {
            // when n is an insertion point
            n = (-n) - 1; // Convert n to an index
            // If n == 0 we don't need to find nearest neighbor. If n is
            // larger than the array, use the last point in the array.
            if (n > values.length - 1) {
                n = values.length - 1; // If key is larger than any value in values
            }
            else if (n > 0) {
                // find nearest neighbor
                double d1 = values[n - 1] - key;
                double d2 = values[n] - key;
                if (d1 <= d2) {
                    n = n - 1;
                }
            }
        }
        return n;
    }

    /**
     * Searches the specified array of doubles for the specified value using
     * the binary search algorithm.  The array <strong>must</strong> be sorted
     * (as by the <tt>sort</tt> method, above) prior to making this call.  If
     * it is not sorted, the results are undefined.  If the array contains
     * multiple elements with the specified value, there is no guarantee which
     * one will be found. The array can be sorted from low values to high or
     * from high values to low.
     *
     * @param a the array to be searched.
     * @param key the value to be searched for.
     * @return index of the search key, if it is contained in the list;
     *         otherwise, <tt>(-(<i>insertion point</i>) - 1)</tt>.  The
     *         <i>insertion point</i> is defined as the point at which the
     *         key would be inserted into the list: the index of the first
     *         element greater than the key, or <tt>list.size()</tt>, if all
     *         elements in the list are less than the specified key.  Note
     *         that this guarantees that the return value will be &gt;= 0 if
     *         and only if the key is found.
     */
    public static int binarySearch(double[] a, double key) {
        int index = -1;
        if (a[0] < a[1]) {
            index = Arrays.binarySearch(a, key);
        }
        else {
            index = binarySearch(a, key, 0, a.length - 1);
        }
        return index;
    }

    private static int binarySearch(double[] a, double key, int low, int high) {
        while (low <= high) {
            int mid = (low + high) / 2;
            double midVal = a[mid];

            int cmp;
            if (midVal > key) {
                cmp = -1; // Neither val is NaN, thisVal is smaller
            }
            else if (midVal < key) {
                cmp = 1; // Neither val is NaN, thisVal is larger
            }
            else {
                long midBits = Double.doubleToLongBits(midVal);
                long keyBits = Double.doubleToLongBits(key);
                cmp = (midBits == keyBits ? 0 : (midBits < keyBits ? -1 : 1)); // (0.0, -0.0) or (NaN, !NaN)
            }

            if (cmp < 0) {
                low = mid + 1;
            }
            else if (cmp > 0) {
                high = mid - 1;
            }
            else {
                return mid; // key found
            }
        }
        return -(low + 1); // key not found.
    }
}
