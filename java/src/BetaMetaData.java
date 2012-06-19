import java.sql.Connection;
import java.sql.SQLException;
import java.sql.DatabaseMetaData;
import javax.sql.DataSource;
import javax.naming.InitialContext;
import javax.naming.Context;
import javax.naming.NamingException;

public class BetaMetaData {
    private static DatabaseMetaData i5meta = null;

    static {
        setI5Source();
    }

    private static void setI5Source () {
        Connection conn = null;
        try {
            Context ctx = new InitialContext();
            DataSource ds = (DataSource)ctx.lookup("java:comp/env/jdbc/beta");
            conn = ds.getConnection();
            i5meta = conn.getMetaData();
            conn.close();
            conn = null;
        } catch (SQLException e) {
            System.out.println(e.getMessage());
        } catch (NamingException e) {
            System.out.println(e.getMessage());
        }
    }

    public static DatabaseMetaData getMetaData() {
        try {
            return i5meta;
        } catch (Exception e) {
            e.printStackTrace();
            return null;
        }
    }

    public Object clone()
        throws CloneNotSupportedException {
            throw new CloneNotSupportedException();
        }
}
