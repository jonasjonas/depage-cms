<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:php="http://php.net/xsl"
    xmlns:dp="http://cms.depagecms.net/ns/depage"
    xmlns:db="http://cms.depagecms.net/ns/database"
    xmlns:proj="http://cms.depagecms.net/ns/project"
    xmlns:pg="http://cms.depagecms.net/ns/page"
    xmlns:func="http://exslt.org/functions"
    extension-element-prefixes="xsl dp func php ">

    <xsl:template match="@*|node()">
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
        </xsl:copy>
    </xsl:template>

    <!-- {{{ update navigation -->
    <xsl:template match="proj:navigations">
        <proj:navigation>
            <xsl:apply-templates select="@*"/>
            <xsl:apply-templates select="proj:navigation[not(substring(@value, 1, 4) = 'tag_')]" mode="navigation"/>
        </proj:navigation>
        <proj:tags>
            <xsl:apply-templates select="proj:navigation[substring(@value, 1, 4) = 'tag_']" mode="tag" />
        </proj:tags>
    </xsl:template>
    <xsl:template match="proj:navigation" mode="navigation">
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
        </xsl:copy>
    </xsl:template>
    <xsl:template match="proj:navigation" mode="tag">
        <proj:tag>
            <xsl:apply-templates select="@*|node()"/>
        </proj:tag>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ update publish targets -->
    <xsl:template match="proj:publish">
        <proj:publishTargets>
            <xsl:apply-templates select="@*"/>
            <xsl:apply-templates select="*"/>
        </proj:publishTargets>
    </xsl:template>
    <xsl:template match="proj:publish_folder">
        <proj:publishTarget>
            <xsl:attribute name="default"><xsl:if test="position() = 1">true</xsl:if><xsl:if test="position() &gt; 1">false</xsl:if></xsl:attribute>

            <xsl:apply-templates select="@*|node()"/>
        </proj:publishTarget>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ update template sets -->
    <xsl:template match="proj:template_sets">
        <proj:templateSets>
            <xsl:apply-templates select="@*|node()"/>
        </proj:templateSets>
    </xsl:template>
    <xsl:template match="proj:template_set">
        <proj:templateSet>
            <xsl:apply-templates select="@*|node()"/>
        </proj:templateSet>
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ delete elements -->
    <xsl:template match="proj:backup|proj:type|@db:name|@db:invalid"></xsl:template>
    <!-- }}} -->

    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>
