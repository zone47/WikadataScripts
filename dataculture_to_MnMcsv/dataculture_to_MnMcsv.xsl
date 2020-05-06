<?xml version="1.0"?>

<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
xmlns:skos="http://www.w3.org/2004/02/skos/core#"
xmlns:dc="http://purl.org/dc/elements/1.1/"
xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
xmlns:owl="http://www.w3.org/2002/07/owl#" >
<xsl:output method="text" />
<xsl:template match="/">
    <xsl:for-each select="//skos:Concept">
        <xsl:variable name="id">
            <xsl:call-template name="string-replace-all">
                <xsl:with-param name="text" select="@rdf:about" />
                <xsl:with-param name="replace">http://data.culture.fr/thesaurus/resource/ark:/67717/</xsl:with-param>
                <xsl:with-param name="by"></xsl:with-param>
            </xsl:call-template>
        </xsl:variable>
        <xsl:value-of select="$id"/><xsl:text>	</xsl:text>
        <xsl:value-of select="skos:prefLabel"/><xsl:text>	</xsl:text>
        <xsl:for-each select="skos:altLabel">
            <xsl:value-of select="."/><xsl:text> (alt.)</xsl:text>
            <xsl:if test="position() != last()"><xsl:text>, </xsl:text></xsl:if>
        </xsl:for-each>
        <xsl:choose>
         <xsl:when test="skos:altLabel and skos:broader"><xsl:text> – </xsl:text></xsl:when>
        </xsl:choose>
        <xsl:for-each select="skos:broader">
            <xsl:if test="position()=1"><xsl:text>Partie de : </xsl:text></xsl:if>
            
            <xsl:call-template name="lb-concept">
                <xsl:with-param name="IDconcept" select="@rdf:resource" />
             </xsl:call-template>
             <xsl:if test="position() != last()"><xsl:text>, </xsl:text></xsl:if>
        </xsl:for-each>
        <xsl:choose>
         <xsl:when test="(skos:altLabel or skos:broader) and skos:narrower"><xsl:text> – </xsl:text></xsl:when>
        </xsl:choose>
        <xsl:for-each select="skos:narrower">
             <xsl:if test="position()=1"><xsl:text>Comprend : </xsl:text></xsl:if>
             <xsl:call-template name="lb-concept">
                <xsl:with-param name="IDconcept" select="@rdf:resource" />
             </xsl:call-template>
             <xsl:if test="position() != last()"><xsl:text>, </xsl:text></xsl:if>
        </xsl:for-each><xsl:text>		</xsl:text>
        <xsl:value-of select="@rdf:about"/><xsl:text>
</xsl:text>
    </xsl:for-each>
</xsl:template>


<xsl:template name="lb-concept">
    <xsl:param name="IDconcept" />
    <xsl:for-each select="//skos:Concept[@rdf:about=$IDconcept]">
        <xsl:value-of select="skos:prefLabel"/>
    </xsl:for-each>
</xsl:template>

<xsl:template name="string-replace-all">
    <xsl:param name="text" />
    <xsl:param name="replace" />
    <xsl:param name="by" />
    <xsl:choose>
        <xsl:when test="$text = '' or $replace = ''or not($replace)" >
            <!-- Prevent this routine from hanging -->
            <xsl:value-of select="$text" />
        </xsl:when>
        <xsl:when test="contains($text, $replace)">
            <xsl:value-of select="substring-before($text,$replace)" />
            <xsl:value-of select="$by" />
            <xsl:call-template name="string-replace-all">
                <xsl:with-param name="text" select="substring-after($text,$replace)" />
                <xsl:with-param name="replace" select="$replace" />
                <xsl:with-param name="by" select="$by" />
            </xsl:call-template>
        </xsl:when>
        <xsl:otherwise>
            <xsl:value-of select="$text" />
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

</xsl:stylesheet> 