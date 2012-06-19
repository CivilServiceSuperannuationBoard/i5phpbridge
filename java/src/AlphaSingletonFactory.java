import javax.servlet.*;

public class AlphaSingletonFactory implements ServletContextListener {
    public static final String POOL = "alphapool";
    public static final String META = "alphameta";

    public void contextInitialized(ServletContextEvent event) {
        ServletContext ctx = event.getServletContext();
        ctx.setAttribute(POOL, new AlphaPool());
        ctx.setAttribute(META, new AlphaMetaData());
    }

    public void contextDestroyed(ServletContextEvent event) {
        ServletContext ctx = event.getServletContext();
        ctx.setAttribute(POOL, null);
        ctx.setAttribute(META, null);
    }

    public static AlphaPool getPoolInstance(ServletContext ctx) {
        return (AlphaPool)ctx.getAttribute(POOL);
    }

    public static AlphaMetaData getMetaInstance(ServletContext ctx) {
        return (AlphaMetaData)ctx.getAttribute(META);
    }
}
