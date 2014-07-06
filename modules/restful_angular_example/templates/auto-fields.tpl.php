
<div ng-app="restfulApp" ng-controller="MainCtrl">
  <form name="article" ng-submit="submitForm()">
    <auto:fields fields="<?php print $schema; ?>" data="<?php print $data; ?>" options="<?php print $options; ?>">

    </auto:fields>
    <button type="submit" class="btn btn-default btn-lg btn-block" ng-class="{'btn-primary':<?php print $data; ?>.$valid}" tabindex="100">Submit</button>
  </form>

  <h2>Console (Server side)</h2>

  <div ng-show="serverSide.status">
    <div>
      Status: {{ serverSide.status }}
    </div>
    <div>
      Data: {{ serverSide.data }}
    </div>

    <div ng-show="serverSide.status == 200">
      New article: <a ng-href="{{ serverSide.data.self }}">{{ serverSide.data.label }}</a>
    </div>
  </div>
</div>
